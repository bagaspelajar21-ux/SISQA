<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\WorkLogAiAnalysisException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\WorkLogAiAnalysisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWorkLogAnalysisController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->analysisData($request));
    }

    public function analyzeWithAi(Request $request, WorkLogAiAnalysisService $service): JsonResponse
    {
        $data = $this->analysisData($request);

        if ($data['work_logs'] === []) {
            return response()->json([
                'analysis' => 'Tidak ada catatan pekerjaan pada periode yang dipilih.',
            ]);
        }

        try {
            return response()->json([
                'analysis' => $service->analyze($data),
            ]);
        } catch (WorkLogAiAnalysisException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->statusCode);
        }
    }

    public function exportPdf(Request $request)
    {
        $data = $this->analysisData($request);
        $fileName = sprintf(
            'Analisis_Pekerjaan_%s_sampai_%s.pdf',
            $data['filters']['start_date'],
            $data['filters']['end_date'],
        );

        return Pdf::loadView('reports.admin_work_log_analysis', $data)
            ->setPaper('a4', 'landscape')
            ->download($fileName);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $data = $this->analysisData($request);
        $spreadsheet = new Spreadsheet;
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Ringkasan');
        $summarySheet->fromArray([
            ['ANALISIS PEKERJAAN'],
            ['Periode', $data['filters']['start_date'].' - '.$data['filters']['end_date']],
            ['Total catatan', $data['summary']['total_entries']],
            ['Hari aktif', $data['summary']['active_days']],
            ['Kontributor', $data['summary']['contributors']],
            ['Rata-rata catatan per hari aktif', $data['summary']['average_entries_per_active_day']],
            [],
            ['Tanggal', 'Jumlah Pekerjaan'],
        ], null, 'A1');

        $row = 9;
        foreach ($data['daily_activity'] as $day) {
            $summarySheet->fromArray([[$day['date'], $day['entry_count']]], null, 'A'.$row);
            $row++;
        }

        $userSheet = $spreadsheet->createSheet();
        $userSheet->setTitle('Per Pengguna');
        $userSheet->fromArray([
            ['No', 'Nama Pengguna', 'Total Catatan', 'Hari Aktif', 'Rata-rata per Hari Aktif'],
        ], null, 'A1');
        foreach ($data['users'] as $index => $user) {
            $row = $index + 2;
            $userSheet->fromArray([[
                $index + 1,
                null,
                $user['entry_count'],
                $user['active_days'],
                $user['average_entries_per_active_day'],
            ]], null, 'A'.$row);
            $userSheet->setCellValueExplicit('B'.$row, $user['name'], DataType::TYPE_STRING);
        }

        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detail Pekerjaan');
        $detailSheet->fromArray([
            ['Tanggal', 'Nama Pengguna', 'Pekerjaan'],
        ], null, 'A1');
        foreach ($data['work_logs'] as $index => $workLog) {
            $row = $index + 2;
            $detailSheet->setCellValueExplicit('A'.$row, $workLog['work_date'], DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit('B'.$row, $workLog['user_name'], DataType::TYPE_STRING);
            $detailSheet->setCellValueExplicit('C'.$row, $workLog['description'], DataType::TYPE_STRING);
        }

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $lastColumn = $sheet->getHighestColumn();
            $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A8A'],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('A1:'.$lastColumn.$sheet->getHighestRow())
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)
                ->setWrapText(true);
            $sheet->freezePane('A2');
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
        }

        $fileName = sprintf(
            'Analisis_Pekerjaan_%s_sampai_%s.xlsx',
            $data['filters']['start_date'],
            $data['filters']['end_date'],
        );

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{
     *     filters: array{start_date: string, end_date: string, user_id: int|null},
     *     summary: array{total_entries: int, active_days: int, contributors: int, average_entries_per_active_day: float},
     *     daily_activity: array<int, array{date: string, entry_count: int}>,
     *     users: array<int, array{user_id: int, name: string, entry_count: int, active_days: int, average_entries_per_active_day: float}>,
     *     work_logs: array<int, array{id: int, user_id: int, user_name: string, work_date: string, description: string}>,
     *     user_options: Collection<int, User>
     * }
     */
    private function analysisData(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $startDate = Carbon::createFromFormat('Y-m-d', $validated['start_date'])->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $validated['end_date'])->endOfDay();

        if ($startDate->diffInDays($endDate) > 365) {
            throw ValidationException::withMessages([
                'end_date' => ['Rentang analisis maksimal 366 hari.'],
            ]);
        }

        $userId = isset($validated['user_id']) ? (int) $validated['user_id'] : null;
        $baseQuery = WorkLog::query()
            ->whereBetween('work_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId));

        $totals = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_entries')
            ->selectRaw('COUNT(DISTINCT work_date) as active_days')
            ->selectRaw('COUNT(DISTINCT user_id) as contributors')
            ->first();

        $totalEntries = (int) $totals->total_entries;
        $activeDays = (int) $totals->active_days;
        $summary = [
            'total_entries' => $totalEntries,
            'active_days' => $activeDays,
            'contributors' => (int) $totals->contributors,
            'average_entries_per_active_day' => $activeDays > 0
                ? round($totalEntries / $activeDays, 1)
                : 0.0,
        ];

        $dailyCounts = (clone $baseQuery)
            ->selectRaw('work_date, COUNT(*) as entry_count')
            ->groupBy('work_date')
            ->pluck('entry_count', 'work_date');
        $dailyActivity = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $date = $cursor->toDateString();
            $dailyActivity[] = [
                'date' => $date,
                'entry_count' => (int) $dailyCounts->get($date, 0),
            ];
            $cursor->addDay();
        }

        $users = (clone $baseQuery)
            ->join('users', 'users.id', '=', 'personal_work_logs.user_id')
            ->select('users.id as user_id', 'users.name')
            ->selectRaw('COUNT(personal_work_logs.id) as entry_count')
            ->selectRaw('COUNT(DISTINCT personal_work_logs.work_date) as active_days')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('entry_count')
            ->orderBy('users.name')
            ->get()
            ->map(fn (object $user): array => [
                'user_id' => (int) $user->user_id,
                'name' => $user->name,
                'entry_count' => (int) $user->entry_count,
                'active_days' => (int) $user->active_days,
                'average_entries_per_active_day' => $user->active_days > 0
                    ? round($user->entry_count / $user->active_days, 1)
                    : 0.0,
            ])
            ->all();

        $workLogs = (clone $baseQuery)
            ->join('users', 'users.id', '=', 'personal_work_logs.user_id')
            ->select(
                'personal_work_logs.id',
                'personal_work_logs.user_id',
                'users.name as user_name',
                'personal_work_logs.work_date',
                'personal_work_logs.description',
            )
            ->orderBy('personal_work_logs.work_date')
            ->orderBy('users.name')
            ->orderBy('personal_work_logs.id')
            ->get()
            ->map(fn (object $workLog): array => [
                'id' => (int) $workLog->id,
                'user_id' => (int) $workLog->user_id,
                'user_name' => $workLog->user_name,
                'work_date' => $workLog->work_date,
                'description' => $workLog->description,
            ])
            ->all();

        return [
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'user_id' => $userId,
            ],
            'summary' => $summary,
            'daily_activity' => $dailyActivity,
            'users' => $users,
            'work_logs' => $workLogs,
            'user_options' => User::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']),
        ];
    }
}
