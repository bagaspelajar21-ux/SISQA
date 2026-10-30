<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationModule;
use App\Models\Bug;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase;
use App\Models\TestExecution;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QaReportController extends Controller
{
    private function getReportData(Request $request): array
    {
        $reportType = $request->input('report_type', 'summary');
        $appId = $request->input('application_id');
        $moduleId = $request->input('module_id');
        $menuId = $request->input('menu_id');
        $testerId = $request->input('tester_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $testStatus = $request->input('test_status');
        $bugSeverity = $request->input('severity');
        $bugStatus = $request->input('bug_status');
        $retestStatus = $request->input('retest_status');

        // Test Cases Base
        $tcQuery = TestCase::with(['application', 'module', 'menu', 'assignedTester']);
        if ($appId) $tcQuery->where('application_id', $appId);
        if ($moduleId) $tcQuery->where('module_id', $moduleId);
        if ($menuId) $tcQuery->where('menu_id', $menuId);
        if ($testerId) $tcQuery->where('assigned_tester_id', $testerId);
        $testCases = $tcQuery->get();
        $tcIds = $testCases->pluck('id');

        // Executions
        $execQuery = TestExecution::with(['testCase.application', 'testCase.module', 'tester', 'schedule'])
            ->whereIn('test_case_id', $tcIds);
        if ($startDate) $execQuery->whereDate('executed_at', '>=', $startDate);
        if ($endDate) $execQuery->whereDate('executed_at', '<=', $endDate);
        if ($testStatus) $execQuery->where('status', $testStatus);
        if ($testerId) $execQuery->where('tester_id', $testerId);
        $executions = $execQuery->orderBy('executed_at', 'desc')->get();

        // Latest executions for summary
        $latestExecutions = DB::table('test_executions as te')
            ->join(DB::raw('(SELECT test_case_id, MAX(executed_at) as max_date FROM test_executions GROUP BY test_case_id) as latest'), function ($join) {
                $join->on('te.test_case_id', '=', 'latest.test_case_id')
                     ->on('te.executed_at', '=', 'latest.max_date');
            })
            ->whereIn('te.test_case_id', $tcIds)
            ->when($startDate, fn($q) => $q->whereDate('te.executed_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('te.executed_at', '<=', $endDate))
            ->select('te.test_case_id', 'te.status', 'te.executed_at')
            ->get();

        $passCount = $latestExecutions->where('status', 'PASS')->count();
        $failedCount = $latestExecutions->where('status', 'FAILED')->count();
        $pendingCount = $latestExecutions->where('status', 'PENDING')->count();
        $executedCount = $latestExecutions->count();
        $notTestedCount = max(0, $testCases->count() - $executedCount);
        $totalValid = $passCount + $failedCount;
        $successRate = $totalValid > 0 ? round(($passCount / $totalValid) * 100, 1) : 0;

        // Bugs
        $bugQuery = Bug::with(['application', 'module', 'menu', 'reporter', 'assignee', 'testCase']);
        if ($appId) $bugQuery->where('application_id', $appId);
        if ($moduleId) $bugQuery->where('module_id', $moduleId);
        if ($menuId) $bugQuery->where('menu_id', $menuId);
        if ($testerId) $bugQuery->where('reporter_id', $testerId);
        if ($startDate) $bugQuery->whereDate('report_date', '>=', $startDate);
        if ($endDate) $bugQuery->whereDate('report_date', '<=', $endDate);
        if ($bugSeverity) $bugQuery->where('severity', $bugSeverity);
        if ($bugStatus) $bugQuery->where('status', $bugStatus);
        $bugs = $bugQuery->orderBy('id', 'desc')->get();

        // Retests
        $retestQuery = Retest::with(['bug.application', 'bug.module', 'bug.menu', 'testCase.module', 'testCase.menu', 'tester']);
        if ($appId) $retestQuery->where('application_id', $appId);
        if ($moduleId) $retestQuery->where('module_id', $moduleId);
        if ($testerId) $retestQuery->where('tester_id', $testerId);
        if ($retestStatus) $retestQuery->where('status', $retestStatus);
        if ($startDate) $retestQuery->whereDate('retest_date', '>=', $startDate);
        if ($endDate) $retestQuery->whereDate('retest_date', '<=', $endDate);
        $retests = $retestQuery->orderBy('id', 'desc')->get();

        // Per-module progress calculation
        $latestStatusByTc = $latestExecutions->pluck('status', 'test_case_id');
        $moduleProgress = [];
        $groupedByModule = $testCases->groupBy(fn($tc) => $tc->module?->name ?: 'Umum');
        foreach ($groupedByModule as $modName => $modTcs) {
            $totalModTc = $modTcs->count();
            $passedModTc = $modTcs->filter(fn($tc) => ($latestStatusByTc[$tc->id] ?? null) === 'PASS')->count();
            $executedModTc = $modTcs->filter(fn($tc) => isset($latestStatusByTc[$tc->id]))->count();
            $progressPct = $totalModTc > 0 ? round(($passedModTc / $totalModTc) * 100) : 0;
            $moduleProgress[] = [
                'module_name' => $modName,
                'total_tc' => $totalModTc,
                'passed_tc' => $passedModTc,
                'executed_tc' => $executedModTc,
                'progress' => $progressPct,
            ];
        }

        // Schedules
        $schQuery = QaSchedule::with(['application', 'module', 'tester', 'testCases']);
        if ($appId) $schQuery->where('application_id', $appId);
        if ($moduleId) $schQuery->where('module_id', $moduleId);
        if ($testerId) $schQuery->where('tester_id', $testerId);
        if ($startDate) $schQuery->whereDate('start_time', '>=', $startDate);
        if ($endDate) $schQuery->whereDate('end_time', '<=', $endDate);
        $schedules = $schQuery->orderBy('start_time', 'desc')->get();

        // Selected app name for header
        $selectedApp = $appId ? Application::find($appId)?->name : 'Semua Aplikasi';
        $selectedModule = $moduleId ? ApplicationModule::find($moduleId)?->name : 'Semua Modul';

        return [
            'report_type' => $reportType,
            'filters' => [
                'application' => $selectedApp,
                'module' => $selectedModule,
                'start_date' => $startDate ?: 'Awal',
                'end_date' => $endDate ?: 'Sekarang',
                'test_status' => $testStatus ?: 'Semua',
                'severity' => $bugSeverity ?: 'Semua',
                'bug_status' => $bugStatus ?: 'Semua',
            ],
            'summary' => [
                'total_test_cases' => $testCases->count(),
                'pass' => $passCount,
                'failed' => $failedCount,
                'pending' => $pendingCount,
                'not_tested' => $notTestedCount,
                'success_rate' => $successRate,
                'total_bugs' => $bugs->count(),
                'active_bugs' => $bugs->whereNotIn('status', ['Closed', 'Rejected'])->count(),
                'critical_major_bugs' => $bugs->whereIn('severity', ['Critical', 'Major'])->count(),
                'total_retests' => $retests->count(),
                'retests_passed' => $retests->where('status', 'Passed')->count(),
                'retests_failed' => $retests->whereIn('status', ['Failed', 'Reopened'])->count(),
                'total_schedules' => $schedules->count(),
            ],
            'test_cases' => $testCases,
            'executions' => $executions,
            'bugs' => $bugs,
            'retests' => $retests,
            'schedules' => $schedules,
            'module_progress' => $moduleProgress,
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $this->getReportData($request);
        return response()->json($data);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $user = $request->user();
        $data = $this->getReportData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan QA');

        // Brand Title
        $sheet->setCellValue('A1', 'SISQA - SOFTWARE QUALITY ASSURANCE SYSTEM');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('2563EB');

        $sheet->setCellValue('A2', 'LAPORAN HASIL QUALITY ASSURANCE & DEFECT TRACKING');
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', "Aplikasi: {$data['filters']['application']} | Modul: {$data['filters']['module']} | Periode: {$data['filters']['start_date']} s/d {$data['filters']['end_date']}");
        $sheet->setCellValue('A4', "Dicetak pada: {$data['generated_at']} oleh {$user->name} ({$user->role})");

        // KPI Summary Box
        $sheet->setCellValue('A6', 'RINGKASAN METRIK QA (KONDISI SAAT INI)');
        $sheet->mergeCells('A6:G6');
        $sheet->getStyle('A6')->getFont()->setBold(true);
        $sheet->getStyle('A6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F4F6');

        $headers = ['Total Test Case', 'PASS', 'FAILED', 'PENDING', 'NOT TESTED', 'Success Rate', 'Total Bug Aktif'];
        $values = [
            $data['summary']['total_test_cases'],
            $data['summary']['pass'],
            $data['summary']['failed'],
            $data['summary']['pending'],
            $data['summary']['not_tested'],
            $data['summary']['success_rate'] . '%',
            $data['summary']['active_bugs'],
        ];

        foreach ($headers as $colIdx => $h) {
            $colLetter = chr(65 + $colIdx);
            $sheet->setCellValue($colLetter . '7', $h);
            $sheet->setCellValue($colLetter . '8', $values[$colIdx]);
            $sheet->getStyle($colLetter . '7')->getFont()->setBold(true);
            $sheet->getStyle($colLetter . '8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Section: Eksekusi Test Case
        $row = 11;
        $sheet->setCellValue('A' . $row, 'RINCIAN HASIL EKSEKUSI PENGUJIAN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
        $row++;

        $tcHeaders = ['No', 'Kode TC', 'Aplikasi', 'Modul', 'Judul Skenario', 'Status', 'Tester', 'Waktu Eksekusi', 'Hasil Aktual'];
        foreach ($tcHeaders as $idx => $th) {
            $colLetter = chr(65 + $idx);
            $sheet->setCellValue($colLetter . $row, $th);
            $sheet->getStyle($colLetter . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($colLetter . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        }
        $row++;

        foreach ($data['executions'] as $eIdx => $exec) {
            $sheet->setCellValue('A' . $row, $eIdx + 1);
            $sheet->setCellValue('B' . $row, $exec->testCase?->test_case_code);
            $sheet->setCellValue('C' . $row, $exec->testCase?->application?->name);
            $sheet->setCellValue('D' . $row, $exec->testCase?->module?->name);
            $sheet->setCellValue('E' . $row, $exec->testCase?->title);
            $sheet->setCellValue('F' . $row, $exec->status);
            $sheet->setCellValue('G' . $row, $exec->tester?->name);
            $sheet->setCellValue('H' . $row, $exec->executed_at?->format('d/m/Y H:i'));
            $sheet->setCellValue('I' . $row, $exec->actual_result);

            // Status color highlight
            $statusCell = 'F' . $row;
            if ($exec->status === 'PASS') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('166534');
            } elseif ($exec->status === 'FAILED') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('991B1B');
            }
            $row++;
        }

        // Section: Daftar Bug
        $row += 2;
        $sheet->setCellValue('A' . $row, 'RINCIAN LAPORAN BUG / DEFECT');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
        $row++;

        $bugHeaders = ['No', 'Kode Bug', 'Aplikasi', 'Judul Bug', 'Severity', 'Priority', 'Status', 'Pelapor', 'Penanggung Jawab', 'Tanggal'];
        foreach ($bugHeaders as $idx => $bh) {
            $colLetter = chr(65 + $idx);
            $sheet->setCellValue($colLetter . $row, $bh);
            $sheet->getStyle($colLetter . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($colLetter . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        }
        $row++;

        foreach ($data['bugs'] as $bIdx => $b) {
            $sheet->setCellValue('A' . $row, $bIdx + 1);
            $sheet->setCellValue('B' . $row, $b->bug_code);
            $sheet->setCellValue('C' . $row, $b->application?->name);
            $sheet->setCellValue('D' . $row, $b->title);
            $sheet->setCellValue('E' . $row, $b->severity);
            $sheet->setCellValue('F' . $row, $b->priority);
            $sheet->setCellValue('G' . $row, $b->status);
            $sheet->setCellValue('H' . $row, $b->reporter?->name);
            $sheet->setCellValue('I' . $row, $b->assignee?->name ?? 'Belum Ditugaskan');
            $sheet->setCellValue('J' . $row, $b->report_date?->format('d/m/Y'));
            $row++;
        }

        // Auto width for columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        ActivityLog::record(
            'qa_report_exported_excel',
            'QA Report',
            "Pengguna {$user->name} mengekspor Laporan QA dalam format Excel (.xlsx).",
            'Report',
            null,
            null,
            ['filters' => $data['filters']],
            'Success',
            $user->id
        );

        $writer = new Xlsx($spreadsheet);
        $fileName = 'SISQA_Report_' . date('Ymd_His') . '.xlsx';

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

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $data = $this->getReportData($request);

        ActivityLog::record(
            'qa_report_exported_pdf',
            'QA Report',
            "Pengguna {$user->name} mengekspor Laporan QA dalam format PDF (.pdf).",
            'Report',
            null,
            null,
            ['filters' => $data['filters']],
            'Success',
            $user->id
        );

        $pdf = Pdf::loadView('reports.qa_report', [
            'data' => $data,
            'user' => $user,
        ]);

        $pdf->setPaper('a4', 'landscape');
        $fileName = 'SISQA_Report_' . date('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }
}
