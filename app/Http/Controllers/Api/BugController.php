<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\Bug;
use App\Models\BugEvidence;
use App\Models\BugHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BugController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bug::with(['application', 'module', 'menu', 'reporter', 'assignee', 'testCase']);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($menuId = $request->input('menu_id')) {
            $query->where('menu_id', $menuId);
        }

        if ($severity = $request->input('severity')) {
            $query->where('severity', $severity);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($reporterId = $request->input('reporter_id')) {
            $query->where('reporter_id', $reporterId);
        }

        if ($assigneeId = $request->input('assignee_id')) {
            $query->where('assignee_id', $assigneeId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('bug_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('report_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('report_date', '<=', $endDate);
        }

        $bugs = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($bugs);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'test_case_id' => 'nullable|exists:test_cases,id',
            'qa_schedule_id' => 'nullable|exists:qa_schedules,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'application_flow_step_id' => 'nullable|exists:application_flow_steps,id',
            'description' => 'required|string',
            'steps_to_reproduce' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'actual_result' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'severity' => ['required', Rule::in(['Critical', 'Major', 'Minor', 'Trivial'])],
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'status' => ['nullable', Rule::in(['Open', 'Assigned', 'In Progress', 'Fixed', 'Ready for Retest', 'Reopened', 'Closed', 'Rejected'])],
            'duplicate_of_id' => 'nullable|exists:bugs,id',
            'evidences' => 'nullable|array',
            'evidences.*' => 'file|max:51200|mimes:png,jpg,jpeg,webp,pdf,mp4',
        ]);

        // Auto-generate bug code
        $yearMonth = date('Ym');
        $count = Bug::where('bug_code', 'like', "BUG-{$yearMonth}-%")->count();
        $validated['bug_code'] = sprintf("BUG-%s-%03d", $yearMonth, $count + 1);
        $validated['reporter_id'] = $user->id;
        $validated['report_date'] = now()->toDateString();
        $validated['status'] = $validated['status'] ?? ($validated['assignee_id'] ? 'Assigned' : 'Open');

        $evidences = $validated['evidences'] ?? [];
        unset($validated['evidences']);

        $bug = Bug::create($validated);

        // Initial History
        BugHistory::create([
            'bug_id' => $bug->id,
            'user_id' => $user->id,
            'field_changed' => 'status',
            'old_value' => null,
            'new_value' => $bug->status,
            'notes' => 'Tiket bug baru dibuat.',
        ]);

        // Handle uploaded initial evidences
        if ($request->hasFile('evidences')) {
            foreach ($request->file('evidences') as $file) {
                $path = $file->store('evidences/bugs', 'local');
                BugEvidence::create([
                    'bug_id' => $bug->id,
                    'evidence_type' => 'initial',
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        ActivityLog::record(
            'bug_created',
            'Bug Tracking',
            "Pengguna {$user->name} melaporkan tiket bug baru: {$bug->title} ({$bug->bug_code}) [Severity: {$bug->severity}]",
            'Bug',
            $bug->id,
            null,
            [
                'bug_code' => $bug->bug_code,
                'severity' => $bug->severity,
                'priority' => $bug->priority,
                'status' => $bug->status,
            ],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Tiket bug berhasil dibuat.',
            'bug' => $bug->load(['application', 'reporter', 'assignee', 'evidences']),
        ], 201);
    }

    public function show(Bug $bug): JsonResponse
    {
        return response()->json([
            'bug' => $bug->load([
                'application',
                'module',
                'menu',
                'testCase',
                'schedule',
                'flow',
                'flowStep',
                'reporter',
                'assignee',
                'duplicateOf',
                'histories.user',
                'evidences.uploader',
                'retests.tester',
                'retests.evidences',
            ]),
        ]);
    }

    public function update(Request $request, Bug $bug): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'test_case_id' => 'nullable|exists:test_cases,id',
            'qa_schedule_id' => 'nullable|exists:qa_schedules,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'application_flow_step_id' => 'nullable|exists:application_flow_steps,id',
            'description' => 'required|string',
            'steps_to_reproduce' => 'nullable|string',
            'expected_result' => 'nullable|string',
            'actual_result' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'severity' => ['required', Rule::in(['Critical', 'Major', 'Minor', 'Trivial'])],
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'status' => ['required', Rule::in(['Open', 'Assigned', 'In Progress', 'Fixed', 'Ready for Retest', 'Reopened', 'Closed', 'Rejected'])],
            'resolution_notes' => 'nullable|string',
            'duplicate_of_id' => 'nullable|exists:bugs,id',
            'history_note' => 'nullable|string',
        ]);

        $historyNote = $validated['history_note'] ?? 'Perubahan informasi tiket bug.';
        unset($validated['history_note']);

        $oldStatus = $bug->status;
        $oldAssignee = $bug->assignee_id;
        $oldSeverity = $bug->severity;

        $bug->update($validated);

        // Record history if status changed
        if ($oldStatus !== $bug->status) {
            BugHistory::create([
                'bug_id' => $bug->id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => $oldStatus,
                'new_value' => $bug->status,
                'notes' => $historyNote,
            ]);
        }

        // Record history if assignee changed
        if ($oldAssignee !== $bug->assignee_id) {
            BugHistory::create([
                'bug_id' => $bug->id,
                'user_id' => $user->id,
                'field_changed' => 'assignee_id',
                'old_value' => (string)$oldAssignee,
                'new_value' => (string)$bug->assignee_id,
                'notes' => 'Penggantian developer/penanggung jawab.',
            ]);
        }

        ActivityLog::record(
            'bug_updated',
            'Bug Tracking',
            "Pengguna {$user->name} memperbarui tiket bug {$bug->bug_code}. Status: {$bug->status}",
            'Bug',
            $bug->id,
            ['status' => $oldStatus, 'severity' => $oldSeverity],
            ['status' => $bug->status, 'severity' => $bug->severity, 'notes' => $historyNote],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Tiket bug berhasil diperbarui.',
            'bug' => $bug->fresh()->load(['application', 'reporter', 'assignee', 'histories.user']),
        ]);
    }

    public function uploadEvidence(Request $request, Bug $bug): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'evidence_type' => ['required', Rule::in(['initial', 'fix', 'retest'])],
            'description' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'file|max:51200|mimes:png,jpg,jpeg,webp,pdf,mp4',
        ]);

        $uploaded = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('evidences/bugs', 'local');
            $ev = BugEvidence::create([
                'bug_id' => $bug->id,
                'evidence_type' => $request->evidence_type,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'uploaded_by' => $user->id,
            ]);
            $uploaded[] = $ev;
        }

        ActivityLog::record(
            'bug_evidence_uploaded',
            'Bug Tracking',
            "Pengguna {$user->name} mengunggah " . count($uploaded) . " berkas evidence ({$request->evidence_type}) pada tiket {$bug->bug_code}",
            'Bug',
            $bug->id,
            null,
            ['count' => count($uploaded), 'type' => $request->evidence_type],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Berkas evidence berhasil diunggah.',
            'evidences' => $uploaded,
        ]);
    }

    public function destroy(Bug $bug): JsonResponse
    {
        $code = $bug->bug_code;
        $bug->delete();

        ActivityLog::record(
            'bug_deleted',
            'Bug Tracking',
            "Admin menghapus tiket bug: {$code}",
            'Bug',
            $bug->id,
            ['bug_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Tiket bug berhasil dihapus.',
        ]);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Bug Tracking');

        $headers = [
            'A' => 'Aplikasi',
            'B' => 'Modul (Menu Utama)',
            'C' => 'Sub-Menu / Fitur',
            'D' => 'Judul Bug / Temuan *',
            'E' => 'Deskripsi Detail Bug *',
            'F' => 'Langkah Reproduksi (Steps to Reproduce)',
            'G' => 'Hasil yang Diharapkan (Expected Result)',
            'H' => 'Hasil Aktual (Actual Result)',
            'I' => 'Severity (Critical/Major/Minor/Trivial)',
            'J' => 'Priority (Urgent/High/Medium/Low)',
            'K' => 'Status (Open/In Progress/Fixed/Ready for Retest/Closed)',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . '1', $text);
            $sheet->getStyle($col . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E11D48');
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $firstApp = Application::first()?->name ?? 'ASIS ERP';
        $sheet->fromArray([
            [
                $firstApp,
                'Penjualan',
                'Faktur Penjualan',
                'Selisih Pembulatan PPN 11% saat Cetak PDF Faktur',
                'Terjadi selisih Rp 1 antara tampilan layar dan cetakan PDF ketika diskon desimal diterapkan.',
                "1. Buka menu Faktur Penjualan\n2. Pilih barang dan beri diskon 5.5%\n3. Klik Cetak PDF",
                'Total PPN di layar dan PDF harus sama persis',
                'Total PPN di PDF selisih Rp 1 lebih tinggi',
                'Major',
                'High',
                'Open',
            ],
            [
                $firstApp,
                'Master Data',
                'Master Pelanggan',
                'Tombol Simpan Tidak Merespon jika NPWP Dikosongkan',
                'Saat kolom NPWP dikosongkan pada pelanggan non-PKP, tombol Simpan tidak memberikan pesan validasi.',
                "1. Buka Master Pelanggan\n2. Klik Tambah\n3. Kosongkan NPWP lalu klik Simpan",
                'Data pelanggan non-PKP tetap tersimpan atau muncul notifikasi',
                'Tombol Simpan tidak merespon sama sekali',
                'Minor',
                'Medium',
                'In Progress',
            ],
        ], null, 'A2');

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Template_Import_BugTracking_SISQA.xlsx';

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

    public function importExcel(Request $request): JsonResponse
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

        $defaultAppId = $request->input('application_id') ?: Application::first()?->id;
        $importedCount = 0;
        $yearMonth = date('Ym');

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue;
            }

            $appName = trim((string) ($row['A'] ?? ''));
            $modName = trim((string) ($row['B'] ?? ''));
            $menuName = trim((string) ($row['C'] ?? ''));
            $title = trim((string) ($row['D'] ?? ''));
            $description = trim((string) ($row['E'] ?? ''));
            $stepsToReproduce = trim((string) ($row['F'] ?? ''));
            $expectedResult = trim((string) ($row['G'] ?? ''));
            $actualResult = trim((string) ($row['H'] ?? ''));
            $severityRaw = ucfirst(strtolower(trim((string) ($row['I'] ?? 'Major'))));
            $priorityRaw = ucfirst(strtolower(trim((string) ($row['J'] ?? 'Medium'))));
            $statusRaw = trim((string) ($row['K'] ?? 'Open'));

            if ($title === '' && $description === '') {
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
            if (!$appId) {
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

                if (!$foundMod) {
                    $codePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $modName), 0, 5) ?: 'MOD');
                    $foundMod = ApplicationModule::create([
                        'application_id' => $appId,
                        'name' => $modName,
                        'code' => $codePrefix . '-' . rand(10, 99),
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

                if (!$foundMenu) {
                    $codePrefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $menuName), 0, 5) ?: 'MNU');
                    $foundMenu = ApplicationMenu::create([
                        'application_id' => $appId,
                        'module_id' => $modId,
                        'name' => $menuName,
                        'code' => $codePrefix . '-' . rand(10, 99),
                    ]);
                }
                $menuId = $foundMenu->id;
            }

            $severity = in_array($severityRaw, ['Critical', 'Major', 'Minor', 'Trivial']) ? $severityRaw : 'Major';
            $priority = in_array($priorityRaw, ['Urgent', 'High', 'Medium', 'Low']) ? $priorityRaw : 'Medium';

            $statusMap = [
                'open' => 'Open',
                'belum fix' => 'Open',
                'assigned' => 'Assigned',
                'in progress' => 'In Progress',
                'on progress' => 'In Progress',
                'fixed' => 'Fixed',
                'ready for retest' => 'Ready for Retest',
                'reopened' => 'Reopened',
                'closed' => 'Closed',
                'rejected' => 'Rejected',
            ];
            $status = $statusMap[strtolower($statusRaw)] ?? 'Open';

            $nextSeq = Bug::where('bug_code', 'like', "BUG-{$yearMonth}-%")->count() + 1;
            $bugCode = sprintf("BUG-%s-%03d", $yearMonth, $nextSeq);
            while (Bug::where('bug_code', $bugCode)->exists()) {
                $nextSeq++;
                $bugCode = sprintf("BUG-%s-%03d", $yearMonth, $nextSeq);
            }

            $bug = Bug::create([
                'bug_code' => $bugCode,
                'title' => $title ?: 'Temuan Bug Baru',
                'application_id' => $appId,
                'module_id' => $modId,
                'menu_id' => $menuId,
                'description' => $description ?: $title,
                'steps_to_reproduce' => $stepsToReproduce ?: null,
                'expected_result' => $expectedResult ?: null,
                'actual_result' => $actualResult ?: null,
                'reporter_id' => $user->id,
                'severity' => $severity,
                'priority' => $priority,
                'status' => $status,
                'report_date' => now()->toDateString(),
            ]);

            BugHistory::create([
                'bug_id' => $bug->id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => null,
                'new_value' => $bug->status,
                'notes' => 'Tiket bug diimpor melalui unggahan Excel.',
            ]);

            $importedCount++;
        }

        ActivityLog::record(
            'bugs_imported_excel',
            'Bug Tracking',
            "Pengguna {$user->name} mengimpor {$importedCount} Tiket Bug dari berkas Excel.",
            'Bug',
            null,
            null,
            ['imported_count' => $importedCount],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => "Berhasil mengimpor {$importedCount} Tiket Bug dari file Excel.",
            'imported_count' => $importedCount,
        ]);
    }
}
