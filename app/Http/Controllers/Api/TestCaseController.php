<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\QaSchedule;
use App\Models\TestCase;
use App\Models\TestExecution;
use App\Models\TestExecutionEvidence;
use App\Services\QaScheduleLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TestCaseController extends Controller
{
    public function index(Request $request, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $dateRange = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $startDate = $dateRange['start_date'] ?? null;
        $endDate = $dateRange['end_date'] ?? null;

        $query = TestCase::with([
            'application',
            'module.schedules',
            'menu.schedules',
            'assignedTester',
            'latestExecution.tester',
            'latestExecution.schedule',
            'schedules' => fn ($scheduleQuery) => $scheduleQuery->orderByDesc('start_time'),
        ]);

        if (! $request->user()->isAdmin()) {
            $scheduleLock->excludeLockedTestCases($query);
        }

        if ($startDate || $endDate) {
            $query->inQaDateRange($startDate, $endDate);
        }

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($menuId = $request->input('menu_id')) {
            $query->where('menu_id', $menuId);
        }

        if ($scheduleId = $request->input('qa_schedule_id')) {
            $schedule = QaSchedule::find($scheduleId);
            if ($schedule !== null) {
                $query->whereIn('id', $schedule->coveredTestCaseIds());
            }
        }

        if ($testerId = $request->input('tester_id')) {
            $query->where('assigned_tester_id', $testerId);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('test_case_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'NOT TESTED') {
                $query->whereDoesntHave('executions');
            } else {
                $query->whereHas('latestExecution', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }
        }

        $testCases = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        $isAdmin = $request->user()->isAdmin();
        $testCases->getCollection()->transform(function ($tc) use ($isAdmin, $scheduleLock) {
            $schedules = $tc->schedules
                ->concat($tc->module?->schedules ?? collect())
                ->concat($tc->menu?->schedules ?? collect());
            $latestExpired = $scheduleLock->expiredScheduleFromSchedules($schedules);
            $tc->is_schedule_expired = $latestExpired !== null;
            $tc->expired_schedule_code = $latestExpired?->schedule_code;
            $tc->expired_schedule_end = $latestExpired?->end_time?->format('d/m/Y H:i');
            $tc->can_continue_qa = $isAdmin || ! $tc->is_schedule_expired;

            return $tc;
        });

        $response = $testCases->toArray();
        $response['qa_lock_policy'] = $scheduleLock->policySummary();

        return response()->json($response);
    }

    public function store(Request $request, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'application_flow_step_id' => 'nullable|exists:application_flow_steps,id',
            'description' => 'nullable|string',
            'preconditions' => 'nullable|string',
            'test_steps' => 'nullable|string',
            'test_data' => 'nullable|string',
            'expected_result' => 'required|string',
            'assigned_tester_id' => 'nullable|exists:users,id',
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'is_active' => 'boolean',
            'qa_schedule_ids' => 'nullable|array',
            'qa_schedule_ids.*' => 'exists:qa_schedules,id',
        ]);

        if (! $request->user()->isAdmin()
            && ($lockedSchedule = $scheduleLock->expiredScheduleForModuleMenu(
                isset($validated['module_id']) ? (int) $validated['module_id'] : null,
                isset($validated['menu_id']) ? (int) $validated['menu_id'] : null,
            )) !== null) {
            return response()->json([
                'message' => $scheduleLock->lockMessage($lockedSchedule),
            ], 403);
        }

        // Auto-generate test case code
        $prefix = 'TC';

        $existingCodes = TestCase::withTrashed()
            ->where('test_case_code', 'like', "{$prefix}-%")
            ->pluck('test_case_code');

        $lastNumber = $existingCodes->map(function ($code) {
            if (preg_match('/^TC-(\d+)$/', $code, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        })->max() ?? 0;

        $validated['test_case_code'] = sprintf(
            '%s-%04d',
            $prefix,
            $lastNumber + 1
        );
        $validated['is_active'] = $validated['is_active'] ?? true;

        $scheduleIds = $validated['qa_schedule_ids'] ?? null;
        unset($validated['qa_schedule_ids']);

        $testCase = TestCase::create($validated);

        if ($scheduleIds !== null) {
            $this->syncScheduleLinks($testCase, $scheduleIds);
        }

        ActivityLog::record(
            'test_case_created',
            'Test Management',
            "Admin membuat test case: {$testCase->title} ({$testCase->test_case_code})",
            'TestCase',
            $testCase->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Test case berhasil dibuat.',
            'test_case' => $testCase->load(['application', 'module', 'menu', 'assignedTester']),
        ], 201);
    }

    public function show(Request $request, TestCase $testCase, QaScheduleLockService $scheduleLock): JsonResponse
    {
        if (! $request->user()->isAdmin()
            && ($lockedSchedule = $scheduleLock->expiredScheduleForTestCase($testCase)) !== null) {
            return response()->json([
                'message' => $scheduleLock->lockMessage($lockedSchedule),
            ], 403);
        }

        return response()->json([
            'test_case' => $testCase->load([
                'application',
                'module',
                'menu',
                'flow',
                'flowStep',
                'assignedTester',
                'executions.tester',
                'executions.evidences',
                'bugs',
            ]),
        ]);
    }

    public function update(Request $request, TestCase $testCase, QaScheduleLockService $scheduleLock): JsonResponse
    {
        if (! $request->user()->isAdmin()
            && ($lockedSchedule = $scheduleLock->expiredScheduleForTestCase($testCase)) !== null) {
            return response()->json([
                'message' => $scheduleLock->lockMessage($lockedSchedule),
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'application_flow_step_id' => 'nullable|exists:application_flow_steps,id',
            'description' => 'nullable|string',
            'preconditions' => 'nullable|string',
            'test_steps' => 'nullable|string',
            'test_data' => 'nullable|string',
            'expected_result' => 'required|string',
            'assigned_tester_id' => 'nullable|exists:users,id',
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'is_active' => 'boolean',
            'qa_schedule_ids' => 'nullable|array',
            'qa_schedule_ids.*' => 'exists:qa_schedules,id',
        ]);

        if (! $request->user()->isAdmin()
            && ($lockedSchedule = $scheduleLock->expiredScheduleForModuleMenu(
                isset($validated['module_id']) ? (int) $validated['module_id'] : null,
                isset($validated['menu_id']) ? (int) $validated['menu_id'] : null,
            )) !== null) {
            return response()->json([
                'message' => "Modul/menu tujuan terkunci karena jadwal QA ({$lockedSchedule->schedule_code}) telah berakhir.",
            ], 403);
        }

        $scheduleIds = $validated['qa_schedule_ids'] ?? null;
        unset($validated['qa_schedule_ids']);

        $old = $testCase->only(['title', 'priority', 'assigned_tester_id', 'is_active', 'expected_result']);
        $testCase->update($validated);

        if ($scheduleIds !== null) {
            $this->syncScheduleLinks($testCase, $scheduleIds);
        }

        ActivityLog::record(
            'test_case_updated',
            'Test Management',
            "Admin memperbarui test case {$testCase->test_case_code}",
            'TestCase',
            $testCase->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Test case berhasil diperbarui.',
            'test_case' => $testCase->fresh()->load(['application', 'module', 'menu', 'assignedTester']),
        ]);
    }

    public function destroy(Request $request, TestCase $testCase, QaScheduleLockService $scheduleLock): JsonResponse
    {
        if (! $request->user()->isAdmin()
            && ($lockedSchedule = $scheduleLock->expiredScheduleForTestCase($testCase)) !== null) {
            return response()->json([
                'message' => $scheduleLock->lockMessage($lockedSchedule),
            ], 403);
        }

        $code = $testCase->test_case_code;
        $testCase->delete();

        ActivityLog::record(
            'test_case_deleted',
            'Test Management',
            "Admin menghapus test case: {$code}",
            'TestCase',
            $testCase->id,
            ['test_case_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Test case berhasil dihapus.',
        ]);
    }

    public function execute(Request $request, TestCase $testCase, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['required', Rule::in(['PASS', 'FAILED', 'PENDING', 'NOT TESTED'])],
            'actual_result' => 'nullable|string',
            'notes' => 'nullable|string',
            'feature_description' => ['nullable', 'string', 'max:5000'],
            'qa_schedule_id' => 'nullable|exists:qa_schedules,id',
            'evidences' => 'nullable|array',
            'evidences.*' => 'file|max:51200|mimes:png,jpg,jpeg,webp,pdf,mp4',
        ]);

        $schedule = null;
        if (! empty($validated['qa_schedule_id'])) {
            $schedule = QaSchedule::find($validated['qa_schedule_id']);

            if ($schedule === null) {
                return response()->json([
                    'message' => 'Jadwal QA yang dipilih tidak ditemukan.',
                    'errors' => ['qa_schedule_id' => ['Jadwal QA yang dipilih tidak ditemukan.']],
                ], 422);
            }

            // Test case harus berada dalam cakupan jadwal (submenu tercakup
            // atau terdaftar eksplisit pada pivot jadwal).
            $isCovered = $schedule->coveredTestCaseIds()->contains($testCase->id)
                || ($testCase->menu_id !== null && $schedule->coversMenu((int) $testCase->menu_id));

            if (! $isCovered) {
                return response()->json([
                    'message' => 'Test case ini tidak termasuk dalam cakupan jadwal QA yang dipilih.',
                    'errors' => [
                        'qa_schedule_id' => ['Test case ini tidak termasuk dalam cakupan jadwal QA yang dipilih.'],
                    ],
                ], 422);
            }

            if (! $user->isAdmin()) {
                if (! $schedule->isAssignedTo($user->id)) {
                    return response()->json([
                        'message' => 'Anda tidak ditugaskan untuk mengerjakan jadwal QA ini.',
                    ], 403);
                }

                if (in_array($schedule->status, ['Cancelled', 'Completed'], true)) {
                    return response()->json([
                        'message' => "Jadwal QA ({$schedule->schedule_code}) sudah {$schedule->status} dan tidak dapat digunakan untuk eksekusi.",
                    ], 403);
                }

                if ($schedule->start_time !== null && now()->lessThan($schedule->start_time)) {
                    return response()->json([
                        'message' => "Jadwal QA ({$schedule->schedule_code}) belum dimulai. Eksekusi dapat dilakukan mulai ".$schedule->start_time->format('d/m/Y H:i').'.',
                    ], 403);
                }
            }
        }

        $lockedSchedule = $request->user()->isAdmin()
            ? null
            : $scheduleLock->expiredScheduleForContext(
                isset($validated['qa_schedule_id']) ? (int) $validated['qa_schedule_id'] : null,
                $testCase->id,
                $testCase->module_id,
                $testCase->menu_id,
            );

        if ($lockedSchedule !== null) {
            return response()->json([
                'message' => $scheduleLock->lockMessage($lockedSchedule),
            ], 403);
        }

        $execution = DB::transaction(function () use ($request, $testCase, $user, $validated) {
            $execution = TestExecution::create([
                'test_case_id' => $testCase->id,
                'qa_schedule_id' => $validated['qa_schedule_id'] ?? null,
                'tester_id' => $user->id,
                'status' => $validated['status'],
                'actual_result' => $validated['actual_result'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'feature_description' => $validated['feature_description'] ?? null,
                'executed_at' => now(),
            ]);

            // Handle uploaded evidences
            if ($request->hasFile('evidences')) {
                foreach ($request->file('evidences') as $file) {
                    $origName = $file->getClientOriginalName();
                    $path = $file->store('evidences/executions', 'local');

                    TestExecutionEvidence::create([
                        'test_execution_id' => $execution->id,
                        'file_name' => basename($path),
                        'original_name' => $origName,
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }

            return $execution;
        });

        ActivityLog::record(
            'test_executed',
            'Test Management',
            "Tester {$user->name} mengeksekusi test case {$testCase->test_case_code} dengan status {$execution->status}",
            'TestExecution',
            $execution->id,
            null,
            [
                'test_case_code' => $testCase->test_case_code,
                'status' => $execution->status,
                'actual_result' => $execution->actual_result,
                'feature_description' => $execution->feature_description,
            ],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Hasil testing berhasil disimpan.',
            'execution' => $execution->load(['tester', 'evidences']),
        ], 201);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Test Case');

        $headers = [
            'A' => 'Aplikasi',
            'B' => 'Modul (Menu Utama)',
            'C' => 'Sub-Menu / Fitur',
            'D' => 'Judul Skenario Test Case *',
            'E' => 'Prioritas (Urgent/High/Medium/Low)',
            'F' => 'Prasyarat (Preconditions)',
            'G' => 'Langkah Pengujian (Test Steps)',
            'H' => 'Data Uji (Test Data)',
            'I' => 'Hasil yang Diharapkan (Expected Result) *',
            'J' => 'Status Eksekusi (PASS/FAILED/PENDING/NOT TESTED)',
            'K' => 'Hasil Aktual Eksekusi',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col.'1', $text);
            $sheet->getStyle($col.'1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col.'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $firstApp = Application::first()?->name ?? 'ASIS ERP';
        $sheet->fromArray([
            [
                $firstApp,
                'Master Data',
                'Master Barang',
                'Validasi Tambah Data Barang Baru dengan Kode Unik',
                'High',
                'User sudah login sebagai Admin Gudang',
                "1. Buka menu Master Data -> Master Barang\n2. Klik tombol Tambah\n3. Isi Kode, Nama, Satuan, dan Harga\n4. Klik Simpan",
                'Kode: BRG-001, Nama: Monitor 24 Inch',
                'Data barang berhasil tersimpan dan muncul di tabel daftar barang',
                'PASS',
                'Data tersimpan sesuai ekspektasi tanpa error',
            ],
            [
                $firstApp,
                'Penjualan',
                'Faktur Penjualan',
                'Validasi Perhitungan PPN 11% Otomatis pada Faktur',
                'Urgent',
                'Terdapat item barang di keranjang penjualan',
                "1. Buka menu Faktur Penjualan\n2. Pilih pelanggan & item barang\n3. Aktifkan opsi PPN 11%\n4. Periksa Grand Total",
                'Harga: Rp 1.000.000, PPN: 11%',
                'Nilai PPN otomatis terhitung Rp 110.000 dan Grand Total Rp 1.110.000',
                'NOT TESTED',
                '',
            ],
        ], null, 'A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_TestCase_SISQA.xlsx';

        return new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
                'Cache-Control' => 'max-age=0',
            ]
        );
    }

    public function importExcel(Request $request, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'file' => 'required|file|max:20480',
            'application_id' => 'nullable|exists:applications,id',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $defaultAppId = $request->input('application_id')
            ? (int) $request->input('application_id')
            : Application::first()?->id;
        if (! $user->isAdmin()
            && ($lockedSchedule = $this->expiredScheduleForImportRows($rows, $defaultAppId, $scheduleLock)) !== null) {
            return response()->json([
                'message' => "Impor dibatalkan karena modul/menu terkunci oleh jadwal QA ({$lockedSchedule->schedule_code}) yang telah berakhir.",
            ], 403);
        }

        $importedCount = 0;

        foreach ($rows as $rowIndex => $row) {
            // Skip header row
            if ($rowIndex === 1) {
                continue;
            }

            $appName = trim((string) ($row['A'] ?? ''));
            $modName = trim((string) ($row['B'] ?? ''));
            $menuName = trim((string) ($row['C'] ?? ''));
            $title = trim((string) ($row['D'] ?? ''));
            $priorityRaw = ucfirst(strtolower(trim((string) ($row['E'] ?? 'Medium'))));
            $preconditions = trim((string) ($row['F'] ?? ''));
            $testSteps = trim((string) ($row['G'] ?? ''));
            $testData = trim((string) ($row['H'] ?? ''));
            $expectedResult = trim((string) ($row['I'] ?? ''));
            $execStatusRaw = strtoupper(trim((string) ($row['J'] ?? '')));
            $actualResult = trim((string) ($row['K'] ?? ''));

            if ($title === '' && $expectedResult === '') {
                continue;
            }

            // Resolve Application
            $appId = $defaultAppId;
            if ($appName !== '') {
                $foundApp = Application::where('name', 'like', "%{$appName}%")
                    ->orWhere('code', 'like', "%{$appName}%")
                    ->first();
                if ($foundApp) {
                    $appId = $foundApp->id;
                }
            }
            if (! $appId) {
                continue;
            }

            // Resolve or Auto-create Module
            $modId = null;
            if ($modName !== '') {
                $foundMod = ApplicationModule::where('application_id', $appId)
                    ->where(function ($q) use ($modName) {
                        $q->where('name', 'like', $modName)
                            ->orWhere('code', 'like', $modName);
                    })->first();

                if (! $foundMod) {
                    $codePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $modName), 0, 5) ?: 'MOD');
                    $foundMod = ApplicationModule::create([
                        'application_id' => $appId,
                        'name' => $modName,
                        'code' => $codePrefix.'-'.rand(10, 99),
                        'description' => 'Diimpor dari Excel',
                    ]);
                }
                $modId = $foundMod->id;
            }

            // Resolve or Auto-create Sub-Menu
            $menuId = null;
            if ($menuName !== '' && $modId) {
                $foundMenu = ApplicationMenu::where('module_id', $modId)
                    ->where('name', 'like', $menuName)
                    ->first();

                if (! $foundMenu) {
                    $codePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $menuName), 0, 5) ?: 'MNU');
                    $foundMenu = ApplicationMenu::create([
                        'application_id' => $appId,
                        'module_id' => $modId,
                        'name' => $menuName,
                        'code' => $codePrefix.'-'.rand(10, 99),
                    ]);
                }
                $menuId = $foundMenu->id;
            }

            $priority = in_array($priorityRaw, ['Urgent', 'High', 'Medium', 'Low']) ? $priorityRaw : 'Medium';

            $existingCodes = TestCase::withTrashed()
                ->where('test_case_code', 'like', 'TC-%')
                ->pluck('test_case_code');

            $lastNumber = $existingCodes->map(function ($code) {
                if (preg_match('/^TC-(\d+)$/', $code, $matches)) {
                    return (int) $matches[1];
                }

                return 0;
            })->max() ?? 0;

            $nextNum = $lastNumber + 1;
            $tcCode = sprintf('TC-%04d', $nextNum);

            while (TestCase::withTrashed()->where('test_case_code', $tcCode)->exists()) {
                $nextNum++;
                $tcCode = sprintf('TC-%04d', $nextNum);
            }

            $tc = TestCase::create([
                'test_case_code' => $tcCode,
                'title' => $title ?: 'Skenario Pengujian Baru',
                'application_id' => $appId,
                'module_id' => $modId,
                'menu_id' => $menuId,
                'preconditions' => $preconditions ?: null,
                'test_steps' => $testSteps ?: null,
                'test_data' => $testData ?: null,
                'expected_result' => $expectedResult ?: 'Sistem berjalan sesuai ekspektasi.',
                'assigned_tester_id' => $user->id,
                'priority' => $priority,
                'is_active' => true,
            ]);

            if (in_array($execStatusRaw, ['PASS', 'FAILED', 'PENDING'])) {
                TestExecution::create([
                    'test_case_id' => $tc->id,
                    'tester_id' => $user->id,
                    'status' => $execStatusRaw,
                    'actual_result' => $actualResult ?: ($execStatusRaw === 'PASS' ? 'Sesuai ekspektasi (Import Excel)' : 'Diimpor dari Excel'),
                    'executed_at' => now(),
                ]);
            }

            $importedCount++;
        }

        ActivityLog::record(
            'test_cases_imported_excel',
            'Test Management',
            "Pengguna {$user->name} mengimpor {$importedCount} Test Case dari berkas Excel.",
            'TestCase',
            null,
            null,
            ['imported_count' => $importedCount],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => "Berhasil mengimpor {$importedCount} Test Case dari file Excel.",
            'imported_count' => $importedCount,
        ]);
    }

    /**
     * Hubungkan test case ke jadwal QA yang cakupannya memuat submenu test case.
     * Jadwal yang sudah selesai/terkunci tidak ditawarkan sebagai tautan baru.
     *
     * @param  array<int, int>  $scheduleIds
     */
    private function syncScheduleLinks(TestCase $testCase, array $scheduleIds): void
    {
        $schedules = QaSchedule::query()
            ->whereIn('id', $scheduleIds)
            ->with('menus')
            ->get();

        $invalid = $schedules->first(function (QaSchedule $schedule) use ($testCase): bool {
            if (in_array($schedule->status, ['Completed', 'Cancelled'], true)) {
                return true;
            }

            $covered = $schedule->coveredTestCaseIds();

            return ! $covered->contains($testCase->id)
                && ($testCase->menu_id === null || ! $schedule->coversMenu((int) $testCase->menu_id));
        });

        if ($invalid !== null) {
            throw ValidationException::withMessages([
                'qa_schedule_ids' => ["Jadwal QA {$invalid->schedule_code} tidak mencakup submenu test case ini."],
            ]);
        }

        $testCase->schedules()->sync($scheduleIds);
    }

    private function expiredScheduleForImportRows(
        array $rows,
        ?int $defaultAppId,
        QaScheduleLockService $scheduleLock,
    ): ?QaSchedule {
        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 1 || (trim((string) ($row['D'] ?? '')) === '' && trim((string) ($row['I'] ?? '')) === '')) {
                continue;
            }

            $appId = $defaultAppId;
            $appName = trim((string) ($row['A'] ?? ''));
            if ($appName !== '') {
                $appId = Application::query()
                    ->where('name', 'like', "%{$appName}%")
                    ->orWhere('code', 'like', "%{$appName}%")
                    ->value('id') ?? $appId;
            }

            $moduleName = trim((string) ($row['B'] ?? ''));
            if (! $appId || $moduleName === '') {
                continue;
            }

            $module = ApplicationModule::query()
                ->where('application_id', $appId)
                ->where(function ($query) use ($moduleName): void {
                    $query->where('name', 'like', $moduleName)
                        ->orWhere('code', 'like', $moduleName);
                })
                ->first();

            if (! $module) {
                continue;
            }

            $menuName = trim((string) ($row['C'] ?? ''));
            $menuId = $menuName === ''
                ? null
                : ApplicationMenu::query()
                    ->where('module_id', $module->id)
                    ->where('name', 'like', $menuName)
                    ->value('id');
            $lockedSchedule = $scheduleLock->expiredScheduleForModuleMenu($module->id, $menuId);

            if ($lockedSchedule !== null) {
                return $lockedSchedule;
            }
        }

        return null;
    }
}
