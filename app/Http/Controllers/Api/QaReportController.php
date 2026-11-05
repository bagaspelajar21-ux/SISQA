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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QaReportController extends Controller
{
    private function getReportData(Request $request): array
    {
        $dateRange = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $reportType = $request->input('report_type', 'summary');
        $appId = $request->input('application_id');
        $moduleId = $request->input('module_id');
        $menuId = $request->input('menu_id');
        $testerId = $request->input('tester_id');
        $startDate = $dateRange['start_date'] ?? null;
        $endDate = $dateRange['end_date'] ?? null;
        $testStatus = $request->input('test_status');
        $bugSeverity = $request->input('severity');
        $bugStatus = $request->input('bug_status');
        $retestStatus = $request->input('retest_status');

        // Test Cases Base
        $tcQuery = TestCase::with(['application', 'module', 'menu', 'assignedTester']);
        if ($startDate || $endDate) {
            $tcQuery->inQaDateRange($startDate, $endDate);
        }
        if ($appId) {
            $tcQuery->where('application_id', $appId);
        }
        if ($moduleId) {
            $tcQuery->where('module_id', $moduleId);
        }
        if ($menuId) {
            $tcQuery->where('menu_id', $menuId);
        }
        if ($testerId) {
            $tcQuery->where('assigned_tester_id', $testerId);
        }
        $testCases = $tcQuery->get();
        $tcIds = $testCases->pluck('id');

        // Executions
        $execQuery = TestExecution::with(['testCase.application', 'testCase.module', 'testCase.menu', 'tester', 'schedule'])
            ->whereIn('test_case_id', $tcIds);
        if ($startDate || $endDate) {
            $execQuery->inQaDateRange($startDate, $endDate);
        }
        if ($testStatus) {
            $execQuery->where('status', $testStatus);
        }
        if ($testerId) {
            $execQuery->where('tester_id', $testerId);
        }
        $executions = $execQuery->orderBy('executed_at', 'desc')->get();

        // Latest executions for summary
        $latestExecutionDates = TestExecution::query()
            ->whereIn('test_case_id', $tcIds)
            ->when($startDate || $endDate, fn ($query) => $query->inQaDateRange($startDate, $endDate))
            ->select('test_case_id')
            ->selectRaw('MAX(executed_at) as max_date')
            ->groupBy('test_case_id');

        $latestExecutions = DB::table('test_executions as te')
            ->joinSub($latestExecutionDates, 'latest', function ($join) {
                $join->on('te.test_case_id', '=', 'latest.test_case_id')
                    ->on('te.executed_at', '=', 'latest.max_date');
            })
            ->whereIn('te.test_case_id', $tcIds)
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
        if ($appId) {
            $bugQuery->where('application_id', $appId);
        }
        if ($moduleId) {
            $bugQuery->where('module_id', $moduleId);
        }
        if ($menuId) {
            $bugQuery->where('menu_id', $menuId);
        }
        if ($testerId) {
            $bugQuery->where('reporter_id', $testerId);
        }
        if ($startDate || $endDate) {
            $bugQuery->inQaDateRange($startDate, $endDate);
        }
        if ($bugSeverity) {
            $bugQuery->where('severity', $bugSeverity);
        }
        if ($bugStatus) {
            $bugQuery->where('status', $bugStatus);
        }
        $bugs = $bugQuery->orderBy('id', 'desc')->get();

        // Retests
        $retestQuery = Retest::with(['bug.application', 'bug.module', 'bug.menu', 'testCase.module', 'testCase.menu', 'tester']);
        if ($appId) {
            $retestQuery->where('application_id', $appId);
        }
        if ($moduleId) {
            $retestQuery->where('module_id', $moduleId);
        }
        if ($testerId) {
            $retestQuery->where('tester_id', $testerId);
        }
        if ($retestStatus) {
            $retestQuery->where('status', $retestStatus);
        }
        if ($startDate || $endDate) {
            $retestQuery->inQaDateRange($startDate, $endDate);
        }
        $retests = $retestQuery->orderBy('id', 'desc')->get();

        // Per-module progress calculation
        $latestStatusByTc = $latestExecutions->pluck('status', 'test_case_id');
        $moduleProgress = [];
        $groupedByModule = $testCases->groupBy(fn ($tc) => implode(':', [
            $tc->application_id ?: 'general',
            $tc->module_id ?: 'general',
            $tc->menu_id ?: 'general',
        ]));
        $executionsByTestCase = $executions->groupBy('test_case_id');
        foreach ($groupedByModule as $modTcs) {
            $modName = $modTcs->first()->module?->name ?: 'Umum';
            $totalModTc = $modTcs->count();
            $passedModTc = $modTcs->filter(fn ($tc) => ($latestStatusByTc[$tc->id] ?? null) === 'PASS')->count();
            $failedModTc = $modTcs->filter(fn ($tc) => ($latestStatusByTc[$tc->id] ?? null) === 'FAILED')->count();
            $notTestedModTc = $totalModTc - $passedModTc - $failedModTc;
            $executedModTc = $modTcs->filter(fn ($tc) => isset($latestStatusByTc[$tc->id]))->count();
            $progressPct = $totalModTc > 0 ? round(($passedModTc / $totalModTc) * 100) : 0;
            $featureDescription = $modTcs
                ->flatMap(fn ($tc) => $executionsByTestCase->get($tc->id, collect())->pluck('feature_description'))
                ->filter(fn (?string $description): bool => is_string($description) && trim($description) !== '')
                ->unique()
                ->implode(' | ');
            $moduleProgress[] = [
                'module_id' => $modTcs->first()->module_id,
                'module_name' => $modName,
                'application_name' => $modTcs->first()->application?->name ?? 'Aplikasi',
                'menu_name' => $modTcs->first()->menu?->name ?? '-',
                'feature_description' => $featureDescription,
                'total_tc' => $totalModTc,
                'passed_tc' => $passedModTc,
                'failed_tc' => $failedModTc,
                'not_tested_tc' => $notTestedModTc,
                'executed_tc' => $executedModTc,
                'progress' => $progressPct,
            ];
        }
        $moduleProgressTotals = [
            'total_tc' => array_sum(array_column($moduleProgress, 'total_tc')),
            'passed_tc' => array_sum(array_column($moduleProgress, 'passed_tc')),
            'failed_tc' => array_sum(array_column($moduleProgress, 'failed_tc')),
            'not_tested_tc' => array_sum(array_column($moduleProgress, 'not_tested_tc')),
        ];

        // Schedules
        $schQuery = QaSchedule::with(['application', 'module', 'tester', 'testCases']);
        if ($appId) {
            $schQuery->where('application_id', $appId);
        }
        if ($moduleId) {
            $schQuery->where('module_id', $moduleId);
        }
        if ($testerId) {
            $schQuery->where('tester_id', $testerId);
        }
        if ($startDate || $endDate) {
            $schQuery->overlappingDateRange($startDate, $endDate);
        }
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
            'module_progress_totals' => $moduleProgressTotals,
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

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ringkasan QA');

        // Brand Title
        $sheet->setCellValue('A1', 'SISQA - SOFTWARE QUALITY ASSURANCE SYSTEM');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('2563EB');

        $sheet->setCellValue('A2', 'LAPORAN HASIL QUALITY ASSURANCE & DEFECT TRACKING');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A3', "Aplikasi: {$data['filters']['application']} | Modul: {$data['filters']['module']} | Periode: {$data['filters']['start_date']} s/d {$data['filters']['end_date']}");
        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A4', "Dicetak pada: {$data['generated_at']} oleh {$user->name} ({$user->role})");
        $sheet->mergeCells('A4:M4');

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
            $data['summary']['success_rate'].'%',
            $data['summary']['active_bugs'],
        ];

        foreach ($headers as $colIdx => $h) {
            $colLetter = chr(65 + $colIdx);
            $sheet->setCellValue($colLetter.'7', $h);
            $sheet->setCellValue($colLetter.'8', $values[$colIdx]);
            $sheet->getStyle($colLetter.'7')->getFont()->setBold(true);
            $sheet->getStyle($colLetter.'8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Section: Eksekusi Test Case
        $row = 10;
        $sheet->setCellValue('A'.$row, 'RINCIAN HASIL EKSEKUSI PENGUJIAN');
        $sheet->mergeCells('A'.$row.':K'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;

        $executionHeaderRow = $row;
        $tcHeaders = [
            'No',
            'Kode TC',
            'Aplikasi',
            'Modul',
            'Judul Skenario',
            'Status QA',
            'Tester',
            'Waktu Eksekusi',
            'Hasil Aktual',
            'Menu',
            'Fitur yang Diuji / Keterangan',
        ];
        foreach ($tcHeaders as $idx => $th) {
            $colLetter = chr(65 + $idx);
            $sheet->setCellValue($colLetter.$row, $th);
            $sheet->getStyle($colLetter.$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($colLetter.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        }
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        foreach ($data['executions'] as $eIdx => $exec) {
            $sheet->setCellValue('A'.$row, $eIdx + 1);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B'.$row, $exec->testCase?->test_case_code);
            $sheet->setCellValue('C'.$row, $exec->testCase?->application?->name);
            $sheet->setCellValue('D'.$row, $exec->testCase?->module?->name);
            $sheet->setCellValue('E'.$row, $exec->testCase?->title);
            $sheet->setCellValue('F'.$row, $exec->status);
            $sheet->setCellValue('G'.$row, $exec->tester?->name);
            $sheet->setCellValue('H'.$row, $exec->executed_at?->format('d/m/Y H:i'));
            $sheet->setCellValue('I'.$row, $exec->actual_result);
            $sheet->setCellValueExplicit('J'.$row, (string) ($exec->testCase?->menu?->name ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('K'.$row, (string) ($exec->feature_description ?? ''), DataType::TYPE_STRING);

            // Status color highlight
            $statusCell = 'F'.$row;
            if ($exec->status === 'PASS') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('166534');
            } elseif ($exec->status === 'FAILED') {
                $sheet->getStyle($statusCell)->getFont()->getColor()->setRGB('991B1B');
            }
            $row++;
        }

        // Section: Daftar Bug
        $row += 2;
        $sheet->setCellValue('A'.$row, 'RINCIAN LAPORAN BUG / DEFECT');
        $sheet->mergeCells('A'.$row.':M'.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(11);
        $row++;

        $bugHeaderRow = $row;
        $bugHeaders = [
            'No',
            'Kode Bug',
            'Aplikasi',
            'Judul Bug',
            'Severity',
            'Priority',
            'Status QA',
            'Pelapor',
            'Penanggung Jawab',
            'Tanggal',
            'Modul',
            'Menu',
            'Keterangan',
        ];
        foreach ($bugHeaders as $idx => $bh) {
            $colLetter = chr(65 + $idx);
            $sheet->setCellValue($colLetter.$row, $bh);
            $sheet->getStyle($colLetter.$row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($colLetter.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E40AF');
        }
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        foreach ($data['bugs'] as $bIdx => $b) {
            $sheet->setCellValue('A'.$row, $bIdx + 1);
            $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->setCellValue('B'.$row, $b->bug_code);
            $sheet->setCellValue('C'.$row, $b->application?->name);
            $sheet->setCellValue('D'.$row, $b->title);
            $sheet->setCellValue('E'.$row, $b->severity);
            $sheet->setCellValue('F'.$row, $b->priority);
            $sheet->setCellValue('G'.$row, $b->status);
            $sheet->setCellValue('H'.$row, $b->reporter?->name);
            $sheet->setCellValue('I'.$row, $b->assignee?->name ?? 'Belum Ditugaskan');
            $sheet->setCellValue('J'.$row, $b->report_date?->format('d/m/Y'));
            $sheet->setCellValue('K'.$row, $b->module?->name);
            $sheet->setCellValueExplicit('L'.$row, (string) ($b->menu?->name ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('M'.$row, (string) $b->description, DataType::TYPE_STRING);
            $row++;
        }

        $mainColumnWidths = [
            'A' => 8,
            'B' => 19,
            'C' => 22,
            'D' => 22,
            'E' => 38,
            'F' => 18,
            'G' => 24,
            'H' => 21,
            'I' => 38,
            'J' => 20,
            'K' => 22,
            'L' => 24,
            'M' => 42,
        ];
        foreach ($mainColumnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getStyle("A{$executionHeaderRow}:K".($executionHeaderRow + count($data['executions'])))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$bugHeaderRow}:M".($bugHeaderRow + count($data['bugs'])))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$executionHeaderRow}:K{$executionHeaderRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$bugHeaderRow}:M{$bugHeaderRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A{$executionHeaderRow}:K".($executionHeaderRow + count($data['executions'])))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('CBD5E1');
        $sheet->getStyle("A{$bugHeaderRow}:M".($bugHeaderRow + count($data['bugs'])))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('CBD5E1');
        $sheet->freezePane('A10');

        $moduleSheet = $spreadsheet->createSheet();
        $moduleSheet->setTitle('Hasil QA Modul');
        $moduleSheet->setCellValue('A1', 'HASIL QA PER MODUL');
        $moduleSheet->mergeCells('A1:I1');
        $moduleSheet->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1E3A8A');
        $moduleSheet->setCellValue('A2', "Periode: {$data['filters']['start_date']} s/d {$data['filters']['end_date']}");
        $moduleSheet->mergeCells('A2:I2');
        $moduleSheet->setCellValue('A3', "Aplikasi: {$data['filters']['application']} | Modul: {$data['filters']['module']}");
        $moduleSheet->mergeCells('A3:I3');

        $moduleHeaderRow = 5;
        $moduleHeaders = [
            'No',
            'Modul',
            'Total Test',
            'PASS',
            'FAIL',
            'NOT TEST',
            'Aplikasi',
            'Menu',
            'Fitur yang Diuji / Keterangan',
        ];
        foreach ($moduleHeaders as $idx => $header) {
            $colLetter = chr(65 + $idx);
            $moduleSheet->setCellValue($colLetter.$moduleHeaderRow, $header);
            $moduleSheet->getStyle($colLetter.$moduleHeaderRow)->getFont()->setBold(true);
            $moduleSheet->getStyle($colLetter.$moduleHeaderRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F4B400');
            $moduleSheet->getStyle($colLetter.$moduleHeaderRow)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        $moduleRow = $moduleHeaderRow + 1;
        foreach ($data['module_progress'] as $moduleIdx => $module) {
            $values = [
                $moduleIdx + 1,
                $module['module_name'],
                $module['total_tc'],
                $module['passed_tc'],
                $module['failed_tc'],
                $module['not_tested_tc'],
                $module['application_name'],
                $module['menu_name'],
                $module['feature_description'],
            ];
            foreach ($values as $idx => $value) {
                $cell = chr(65 + $idx).$moduleRow;
                if ($idx >= 6) {
                    $moduleSheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
                } else {
                    $moduleSheet->setCellValue($cell, $value);
                }
            }
            $moduleSheet->getStyle("A{$moduleRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $moduleSheet->getStyle("C{$moduleRow}:F{$moduleRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $moduleRow++;
        }

        $moduleTotals = $data['module_progress_totals'];
        $moduleSheet->setCellValue('B'.$moduleRow, 'TOTAL');
        $moduleSheet->setCellValue('C'.$moduleRow, $moduleTotals['total_tc']);
        $moduleSheet->setCellValue('D'.$moduleRow, $moduleTotals['passed_tc']);
        $moduleSheet->setCellValue('E'.$moduleRow, $moduleTotals['failed_tc']);
        $moduleSheet->setCellValue('F'.$moduleRow, $moduleTotals['not_tested_tc']);
        $moduleSheet->getStyle("A{$moduleRow}:I{$moduleRow}")->getFont()->setBold(true);
        $moduleSheet->getStyle("A{$moduleRow}:I{$moduleRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FDE68A');
        $moduleSheet->getStyle("B{$moduleRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $moduleSheet->getStyle("C{$moduleRow}:F{$moduleRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['A' => 8, 'B' => 30, 'C' => 14, 'D' => 12, 'E' => 12, 'F' => 14, 'G' => 24, 'H' => 24, 'I' => 48] as $column => $width) {
            $moduleSheet->getColumnDimension($column)->setWidth($width);
        }
        $moduleSheet->getStyle("A{$moduleHeaderRow}:I{$moduleRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('CBD5E1');
        $moduleSheet->getStyle("A{$moduleHeaderRow}:F{$moduleRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $moduleSheet->freezePane('A6');
        $moduleSheet->setAutoFilter("A{$moduleHeaderRow}:I".($moduleRow - 1));
        $spreadsheet->setActiveSheetIndex(0);

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
        $fileName = 'SISQA_Report_'.date('Ymd_His').'.xlsx';

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
        $fileName = 'SISQA_Report_'.date('Ymd_His').'.pdf';

        return $pdf->download($fileName);
    }
}
