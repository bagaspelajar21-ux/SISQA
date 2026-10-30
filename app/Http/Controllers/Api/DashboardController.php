<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Bug;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase;
use App\Models\TestExecution;
use App\Services\RolePermissionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perms = RolePermissionService::getPermissionsForUser($user);
        $allowedMenus = $perms['menus'] ?? [];
        $allowedFeatures = $perms['features'] ?? [];

        if ($user && $user->role !== 'admin' && !in_array('/', $allowedMenus, true)) {
            return response()->json([
                'message' => 'Akses ke menu Dashboard telah dinonaktifkan untuk akun Anda.',
            ], 403);
        }

        $canViewAllData = ($user && $user->role === 'admin') || in_array('dashboard_all_data', $allowedFeatures, true);
        $canViewExecCharts = ($user && $user->role === 'admin') || in_array('dashboard_exec_charts', $allowedFeatures, true);
        $canUseQuickActions = ($user && $user->role === 'admin') || in_array('dashboard_quick_actions', $allowedFeatures, true);

        $appId = $request->input('application_id');
        $moduleId = $request->input('module_id');
        $menuId = $request->input('menu_id');
        $testerId = $canViewAllData ? $request->input('tester_id') : ($user?->id);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Base query for Test Cases
        $tcQuery = TestCase::query()->where('is_active', true);
        if ($appId) $tcQuery->where('application_id', $appId);
        if ($moduleId) $tcQuery->where('module_id', $moduleId);
        if ($menuId) $tcQuery->where('menu_id', $menuId);
        if ($testerId) {
            $tcQuery->where(function ($q) use ($testerId) {
                $q->where('assigned_tester_id', $testerId)
                  ->orWhereIn('id', function ($sub) use ($testerId) {
                      $sub->select('test_case_id')->from('test_executions')->where('tester_id', $testerId);
                  })
                  ->orWhereIn('id', function ($sub) use ($testerId) {
                      $sub->select('qst.test_case_id')
                          ->from('qa_schedule_test_cases as qst')
                          ->join('qa_schedules as qs', 'qs.id', '=', 'qst.qa_schedule_id')
                          ->where('qs.tester_id', $testerId);
                  });
            });
        }

        $testCaseIds = $tcQuery->pluck('id');
        $totalTestCases = $testCaseIds->count();

        // Latest execution per test case in scope
        $latestExecutions = DB::table('test_executions as te')
            ->join(DB::raw('(SELECT test_case_id, MAX(executed_at) as max_date FROM test_executions GROUP BY test_case_id) as latest'), function ($join) {
                $join->on('te.test_case_id', '=', 'latest.test_case_id')
                     ->on('te.executed_at', '=', 'latest.max_date');
            })
            ->whereIn('te.test_case_id', $testCaseIds)
            ->when($startDate, fn($q) => $q->whereDate('te.executed_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('te.executed_at', '<=', $endDate))
            ->when($testerId, fn($q) => $q->where('te.tester_id', $testerId))
            ->select('te.test_case_id', 'te.status', 'te.executed_at')
            ->get();

        $passCount = $latestExecutions->where('status', 'PASS')->count();
        $failedCount = $latestExecutions->where('status', 'FAILED')->count();
        $pendingCount = $latestExecutions->where('status', 'PENDING')->count();
        
        // Not tested = total test cases in scope minus those executed
        $executedTestCaseIds = $latestExecutions->pluck('test_case_id')->unique();
        $notTestedCount = max(0, $totalTestCases - $executedTestCaseIds->count());

        $totalExecuted = $passCount + $failedCount;
        $successRate = $totalExecuted > 0 ? round(($passCount / $totalExecuted) * 100, 1) : 0;

        // Bugs Query
        $bugQuery = Bug::query();
        if ($appId) $bugQuery->where('application_id', $appId);
        if ($moduleId) $bugQuery->where('module_id', $moduleId);
        if ($menuId) $bugQuery->where('menu_id', $menuId);
        if ($testerId) $bugQuery->where('reporter_id', $testerId);
        if ($startDate) $bugQuery->whereDate('report_date', '>=', $startDate);
        if ($endDate) $bugQuery->whereDate('report_date', '<=', $endDate);

        $totalActiveBugs = (clone $bugQuery)->whereNotIn('status', ['Closed', 'Rejected'])->count();
        $criticalMajorBugs = (clone $bugQuery)->whereNotIn('status', ['Closed', 'Rejected'])
            ->whereIn('severity', ['Critical', 'Major'])
            ->count();

        // Schedules Query
        $schQuery = QaSchedule::query();
        if ($appId) $schQuery->where('application_id', $appId);
        if ($moduleId) $schQuery->where('module_id', $moduleId);
        if ($menuId) $schQuery->where('menu_id', $menuId);
        if ($testerId) $schQuery->where('tester_id', $testerId);
        if ($startDate) $schQuery->whereDate('start_time', '>=', $startDate);
        if ($endDate) $schQuery->whereDate('end_time', '<=', $endDate);

        $activeSchedules = (clone $schQuery)->whereIn('status', ['Scheduled', 'In Progress'])->count();
        $overdueSchedules = (clone $schQuery)->where(function ($q) {
            $q->where('status', 'Overdue')
              ->orWhere(function ($sub) {
                  $sub->whereNotIn('status', ['Completed', 'Cancelled'])
                      ->where('end_time', '<', now());
              });
        })->count();

        // Retests waiting
        $retestQuery = Retest::query();
        if ($appId) $retestQuery->where('application_id', $appId);
        if ($moduleId) $retestQuery->where('module_id', $moduleId);
        if ($menuId) $retestQuery->where('menu_id', $menuId);
        if ($testerId) $retestQuery->where('tester_id', $testerId);
        $waitingRetests = $retestQuery->where('status', 'Pending Retest')->count();

        // --- CHARTS DATA ---
        // 1. Status Distribution
        $statusDistribution = [
            'labels' => ['PASS', 'FAILED', 'PENDING', 'NOT TESTED'],
            'counts' => [$passCount, $failedCount, $pendingCount, $notTestedCount],
            'colors' => ['#16A34A', '#DC2626', '#D97706', '#9CA3AF'],
        ];

        // 2. Bugs by Severity (Single grouped query instead of loop)
        $severities = ['Critical', 'Major', 'Minor', 'Trivial'];
        $rawSeverities = (clone $bugQuery)
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');
        $severityCounts = array_map(fn($sev) => (int) ($rawSeverities[$sev] ?? 0), $severities);
        $bugsBySeverity = [
            'labels' => $severities,
            'counts' => $severityCounts,
            'colors' => ['#991B1B', '#EF4444', '#F59E0B', '#9CA3AF'],
        ];

        // 3. Bugs by Status (Single grouped query instead of 8 queries)
        $statuses = ['Open', 'Assigned', 'In Progress', 'Fixed', 'Ready for Retest', 'Reopened', 'Closed', 'Rejected'];
        $rawStatuses = (clone $bugQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $statusCounts = array_map(fn($st) => (int) ($rawStatuses[$st] ?? 0), $statuses);
        $bugsByStatus = [
            'labels' => $statuses,
            'counts' => $statusCounts,
            'colors' => ['#3B82F6', '#6366F1', '#8B5CF6', '#06B6D4', '#EAB308', '#EF4444', '#10B981', '#6B7280'],
        ];

        // 4. Testing per Application (Single aggregated query instead of N+1 queries)
        $apps = Application::where('is_active', true)->get(['id', 'name']);
        $tcCountsByApp = TestCase::where('is_active', true)
            ->select('application_id', DB::raw('count(*) as total'))
            ->groupBy('application_id')
            ->pluck('total', 'application_id');

        $passedByApp = DB::table('test_executions as te')
            ->join(DB::raw('(SELECT test_case_id, MAX(executed_at) as max_date FROM test_executions GROUP BY test_case_id) as latest'), function ($join) {
                $join->on('te.test_case_id', '=', 'latest.test_case_id')
                     ->on('te.executed_at', '=', 'latest.max_date');
            })
            ->join('test_cases as tc', 'tc.id', '=', 'te.test_case_id')
            ->where('tc.is_active', true)
            ->where('te.status', 'PASS')
            ->select('tc.application_id', DB::raw('count(DISTINCT te.test_case_id) as passed'))
            ->groupBy('tc.application_id')
            ->pluck('passed', 'application_id');

        $appsData = [];
        foreach ($apps as $app) {
            $appsData[] = [
                'name' => $app->name,
                'total' => (int) ($tcCountsByApp[$app->id] ?? 0),
                'passed' => (int) ($passedByApp[$app->id] ?? 0),
            ];
        }

        // 5. Test Execution Trend (Last 7 days or weeks)
        $trendsQuery = DB::table('test_executions')
            ->select(DB::raw('DATE(executed_at) as exec_date'), 'status', DB::raw('count(*) as count'))
            ->whereIn('test_case_id', $testCaseIds)
            ->where('executed_at', '>=', now()->subDays(14))
            ->groupBy('exec_date', 'status')
            ->orderBy('exec_date')
            ->get();

        $trendDates = [];
        for ($i = 13; $i >= 0; $i--) {
            $trendDates[] = Carbon::today()->subDays($i)->format('Y-m-d');
        }

        $trendPass = [];
        $trendFailed = [];
        foreach ($trendDates as $d) {
            $passItem = $trendsQuery->first(fn($t) => $t->exec_date === $d && $t->status === 'PASS');
            $failItem = $trendsQuery->first(fn($t) => $t->exec_date === $d && $t->status === 'FAILED');
            $trendPass[] = $passItem ? $passItem->count : 0;
            $trendFailed[] = $failItem ? $failItem->count : 0;
        }

        return response()->json([
            'access_scope' => [
                'is_restricted' => !$canViewAllData,
                'can_view_all_data' => $canViewAllData,
                'can_view_exec_charts' => $canViewExecCharts,
                'can_use_quick_actions' => $canUseQuickActions,
                'scoped_user_name' => $user?->name,
            ],
            'metrics' => [
                'total_test_cases' => $totalTestCases,
                'pass' => $passCount,
                'failed' => $failedCount,
                'pending' => $pendingCount,
                'not_tested' => $notTestedCount,
                'success_rate' => $successRate,
                'total_active_bugs' => $totalActiveBugs,
                'critical_major_bugs' => $criticalMajorBugs,
                'active_schedules' => $activeSchedules,
                'overdue_schedules' => $overdueSchedules,
                'waiting_retests' => $waitingRetests,
            ],
            'calculation_notes' => [
                'success_rate' => 'Dihitung dari hasil PASS dibagi total hasil valid yang dieksekusi (PASS + FAILED) dikalikan 100%. PENDING dan NOT TESTED tidak dihitung sebagai kelulusan.',
                'latest_execution' => 'Status ringkasan saat ini menggunakan 1 hasil eksekusi paling mutakhir untuk setiap test case dalam cakupan filter.',
                'bugs_uniqueness' => 'Jumlah bug aktif dihitung berdasarkan identitas entitas tiket bug yang belum berstatus Closed atau Rejected.',
            ],
            'charts' => [
                'status_distribution' => $statusDistribution,
                'bugs_by_severity' => $bugsBySeverity,
                'bugs_by_status' => $bugsByStatus,
                'testing_per_app' => [
                    'labels' => collect($appsData)->pluck('name'),
                    'total' => collect($appsData)->pluck('total'),
                    'passed' => collect($appsData)->pluck('passed'),
                ],
                'trend' => [
                    'labels' => $trendDates,
                    'pass' => $trendPass,
                    'failed' => $trendFailed,
                ],
            ],
        ]);
    }
}
