<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApplicationMenu;
use App\Models\QaSchedule;
use App\Models\TestCase;
use App\Models\TestExecution;
use App\Services\QaScheduleLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QaScheduleController extends Controller
{
    public function index(Request $request, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $dateRange = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $startDate = $dateRange['start_date'] ?? null;
        $endDate = $dateRange['end_date'] ?? null;

        $query = QaSchedule::with(['application', 'module', 'menu', 'menus', 'tester', 'testers', 'testCases'])
            ->withCount(['menus', 'testCases']);

        $user = $request->user();
        if (! $user->isAdmin()) {
            $query->assignedTo($user->id);
        }

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($testerId = $request->input('tester_id')) {
            if ($user->isAdmin()) {
                $query->where(function ($q) use ($testerId) {
                    $q->where('tester_id', $testerId)
                        ->orWhereHas('testers', fn ($testerQuery) => $testerQuery->whereKey($testerId));
                });
            }
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('schedule_code', 'like', "%{$search}%");
            });
        }

        if ($startDate || $endDate) {
            $query->overlappingDateRange($startDate, $endDate);
        }

        $schedules = $query->orderBy('start_time', 'desc')
            ->paginate($request->input('per_page', 15));

        // Append computed progress & auto-detect overdue/expired schedule time
        $schedules->getCollection()->transform(function ($item) use ($request, $scheduleLock) {
            $isExpired = $item->end_time && now()->gt($item->end_time);
            if ($isExpired && ! in_array($item->status, ['Completed', 'Cancelled', 'Overdue'])) {
                $item->update(['status' => 'Overdue']);
                $item->status = 'Overdue';
            }
            $isLocked = $scheduleLock->expiredSchedule($item) !== null;
            $item->progress_info = $item->progress;
            $item->is_expired = $isExpired;
            $item->is_assigned = $request->user()->isAdmin() || $item->isAssignedTo($request->user()->id);
            $item->can_continue_qa = ! in_array($item->status, ['Completed', 'Cancelled'])
                && $item->is_assigned
                && ($request->user()->isAdmin() || (! $isLocked && ! $item->start_time->isFuture()));

            return $item;
        });

        return response()->json($schedules);
    }

    public function calendar(Request $request): JsonResponse
    {
        $dateRange = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $startDate = $dateRange['start_date'] ?? null;
        $endDate = $dateRange['end_date'] ?? null;

        $query = QaSchedule::with(['application', 'tester', 'testers']);

        $user = $request->user();
        if (! $user->isAdmin()) {
            $query->assignedTo($user->id);
        }

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($testerId = $request->input('tester_id')) {
            if ($user->isAdmin()) {
                $query->where(function ($q) use ($testerId) {
                    $q->where('tester_id', $testerId)
                        ->orWhereHas('testers', fn ($testerQuery) => $testerQuery->whereKey($testerId));
                });
            }
        }

        if ($startDate || $endDate) {
            $query->overlappingDateRange($startDate, $endDate);
        }

        $events = $query->get()->map(function ($sch) {
            $color = match ($sch->status) {
                'Completed' => '#10B981',
                'In Progress' => '#3B82F6',
                'Overdue' => '#EF4444',
                'Cancelled' => '#9CA3AF',
                default => '#6366F1',
            };

            $testers = $sch->testers->pluck('name');
            if ($sch->tester && ! $testers->contains($sch->tester->name)) {
                $testers->push($sch->tester->name);
            }

            return [
                'id' => $sch->id,
                'title' => "[{$sch->schedule_code}] {$sch->title}",
                'start' => $sch->start_time->toISOString(),
                'end' => $sch->end_time->toISOString(),
                'status' => $sch->status,
                'priority' => $sch->priority,
                'tester' => $testers->isNotEmpty() ? $testers->implode(', ') : 'Belum Ditugaskan',
                'application' => $sch->application?->name ?? '-',
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'progress' => $sch->progress,
                    'schedule_code' => $sch->schedule_code,
                    'testing_type' => $sch->testing_type,
                    'menus_count' => $sch->menus->count(),
                ],
            ];
        });

        return response()->json($events);
    }

    public function checkConflict(Request $request): JsonResponse
    {
        $testerIds = $request->input('tester_ids', []);
        if (! is_array($testerIds)) {
            $testerIds = [$testerIds];
        }
        $testerId = $request->input('tester_id');
        if ($testerId && ! in_array($testerId, $testerIds, true)) {
            $testerIds[] = $testerId;
        }
        $testerIds = array_filter(array_map('intval', $testerIds));

        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $excludeId = $request->input('exclude_id');

        if ($testerIds === [] || ! $startTime || ! $endTime) {
            return response()->json(['has_conflict' => false]);
        }

        $conflicts = QaSchedule::where(function ($q) use ($testerIds) {
            $q->whereIn('tester_id', $testerIds)
                ->orWhereHas('testers', fn ($testerQuery) => $testerQuery->whereIn('users.id', $testerIds));
        })
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($sub) use ($startTime, $endTime) {
                        $sub->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->get(['id', 'schedule_code', 'title', 'start_time', 'end_time']);

        return response()->json([
            'has_conflict' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->scheduleRules($request));

        $menuIds = $this->resolveMenuIds($request, $validated);
        $testerIds = $this->resolveTesterIds($request, $validated);
        $testCaseIds = $this->resolveTestCaseIds($request, $validated, null, $menuIds);

        // Jadwal terpusat mewajibkan minimal satu tester; payload lama
        // tanpa field cakupan tetap boleh tanpa tester (kompatibilitas).
        $usesCoverageFields = $request->has('menu_scope') || $request->has('menu_ids') || $request->has('tester_ids');
        if ($testerIds === [] && $usesCoverageFields) {
            throw ValidationException::withMessages([
                'tester_ids' => ['Minimal satu tester harus ditugaskan pada jadwal QA.'],
            ]);
        }

        // Auto-generate code
        $yearMonth = date('Ym');
        $count = QaSchedule::where('schedule_code', 'like', "SCH-{$yearMonth}-%")->count();
        $validated['schedule_code'] = sprintf('SCH-%s-%03d', $yearMonth, $count + 1);
        $validated['status'] = $validated['status'] ?? 'Scheduled';
        $validated['tester_id'] = $testerIds[0] ?? ($validated['tester_id'] ?? null);
        unset($validated['menu_ids'], $validated['tester_ids'], $validated['test_case_ids']);

        if (empty($validated['target_test_cases_count'])) {
            $validated['target_test_cases_count'] = count($testCaseIds);
        }

        $schedule = DB::transaction(function () use ($validated, $menuIds, $testerIds, $testCaseIds) {
            $schedule = QaSchedule::create($validated);
            $schedule->menus()->sync($menuIds);
            $schedule->testers()->sync($testerIds);
            $schedule->testCases()->sync($testCaseIds);

            return $schedule;
        });

        ActivityLog::record(
            'schedule_created',
            'Jadwal QA',
            "Admin membuat jadwal QA: {$schedule->title} ({$schedule->schedule_code})",
            'QaSchedule',
            $schedule->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil dibuat.',
            'schedule' => $schedule->load(['application', 'module', 'menus', 'tester', 'testers', 'testCases']),
        ], 201);
    }

    public function show(Request $request, QaSchedule $schedule, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $schedule->isAssignedTo($user->id)) {
            return response()->json([
                'message' => 'Anda tidak ditugaskan pada jadwal QA ini.',
            ], 403);
        }

        $isExpired = $schedule->end_time && now()->gt($schedule->end_time);
        if ($isExpired && ! in_array($schedule->status, ['Completed', 'Cancelled', 'Overdue'])) {
            $schedule->update(['status' => 'Overdue']);
            $schedule->status = 'Overdue';
        }

        $schedule->load(['application', 'module', 'menu', 'menus', 'flow', 'tester', 'testers', 'testCases.latestExecution', 'testExecutions.tester', 'bugs']);
        $data = $schedule->toArray();
        $data['progress_info'] = $schedule->progress;
        $data['is_expired'] = $isExpired;
        $data['is_assigned'] = $user->isAdmin() || $schedule->isAssignedTo($user->id);
        $data['can_continue_qa'] = ! in_array($schedule->status, ['Completed', 'Cancelled'])
            && $data['is_assigned']
            && ($user->isAdmin() || ($scheduleLock->expiredSchedule($schedule) === null && ! $schedule->start_time->isFuture()));
        $data['menus_coverage'] = $this->menusCoverage($schedule);

        return response()->json(['schedule' => $data]);
    }

    public function update(Request $request, QaSchedule $schedule): JsonResponse
    {
        if ($schedule->hasPassedEndTime() && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => "Jadwal QA ({$schedule->schedule_code}) sudah berakhir dan terkunci. Hanya Admin yang dapat mengubah jadwal ini.",
            ], 403);
        }

        $validated = $request->validate($this->scheduleRules($request, $schedule));

        // Payload lama tanpa field cakupan tidak boleh menimpa pivot yang ada.
        $menuCoverageChanged = $request->has('menu_ids') || $request->has('menu_scope') || $request->has('menu_id');
        $testerChanged = $request->has('tester_ids') || $request->has('tester_id');
        $testCasesChanged = $request->has('test_case_ids');

        $menuIds = $menuCoverageChanged
            ? $this->resolveMenuIds($request, $validated, $schedule)
            : null;
        $testerIds = $testerChanged
            ? $this->resolveTesterIds($request, $validated, $schedule)
            : null;
        $testCaseIds = $this->resolveTestCaseIds($request, $validated, $schedule, $menuIds);

        if ($testerIds !== null && $testerIds === []) {
            throw ValidationException::withMessages([
                'tester_ids' => ['Minimal satu tester harus ditugaskan pada jadwal QA.'],
            ]);
        }

        $old = $schedule->only(['title', 'status', 'tester_id', 'start_time', 'end_time', 'priority']);
        if ($testerIds !== null) {
            $validated['tester_id'] = $testerIds[0] ?? ($validated['tester_id'] ?? $schedule->tester_id);
        }
        unset($validated['menu_ids'], $validated['tester_ids'], $validated['test_case_ids']);

        if (empty($validated['target_test_cases_count']) && $testCaseIds !== null) {
            $validated['target_test_cases_count'] = count($testCaseIds);
        }

        DB::transaction(function () use ($schedule, $validated, $menuIds, $testerIds, $testCaseIds) {
            $schedule->update($validated);
            if ($menuIds !== null) {
                $schedule->menus()->sync($menuIds);
            }
            if ($testerIds !== null) {
                $schedule->testers()->sync($testerIds);
            }
            if ($testCaseIds !== null) {
                $schedule->testCases()->sync($testCaseIds);
            }
        });

        ActivityLog::record(
            'schedule_updated',
            'Jadwal QA',
            "Jadwal QA {$schedule->schedule_code} diperbarui. Status: {$schedule->status}",
            'QaSchedule',
            $schedule->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil diperbarui.',
            'schedule' => $schedule->fresh()->load(['application', 'module', 'menus', 'tester', 'testers', 'testCases']),
        ]);
    }

    public function destroy(Request $request, QaSchedule $schedule): JsonResponse
    {
        if ($schedule->hasPassedEndTime() && ! $request->user()->isAdmin()) {
            return response()->json([
                'message' => "Jadwal QA ({$schedule->schedule_code}) sudah berakhir dan terkunci. Hanya Admin yang dapat menghapus jadwal ini.",
            ], 403);
        }

        $code = $schedule->schedule_code;
        $schedule->delete();

        ActivityLog::record(
            'schedule_deleted',
            'Jadwal QA',
            "Admin menghapus jadwal QA: {$code}",
            'QaSchedule',
            $schedule->id,
            ['schedule_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil dihapus.',
        ]);
    }

    /**
     * Daftar submenu dalam cakupan jadwal beserta statistik eksekusi.
     */
    public function menus(Request $request, QaSchedule $schedule): JsonResponse
    {
        $this->authorizeScheduleAccess($request, $schedule);

        return response()->json([
            'menus' => $this->menusCoverage($schedule),
        ]);
    }

    /**
     * Test case dalam cakupan jadwal untuk satu submenu tertentu.
     * menu_id = 0 berarti test case level modul (tanpa submenu).
     */
    public function testCases(Request $request, QaSchedule $schedule): JsonResponse
    {
        $this->authorizeScheduleAccess($request, $schedule);

        $validated = $request->validate([
            'menu_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $menuId = isset($validated['menu_id']) ? (int) $validated['menu_id'] : null;

        $coveredIds = $schedule->coveredTestCaseIds();

        $query = TestCase::query()
            ->whereIn('id', $coveredIds)
            ->with(['assignedTester'])
            ->orderBy('test_case_code');

        if ($menuId === 0) {
            $query->whereNull('menu_id')->where('module_id', $schedule->module_id);
        } elseif ($menuId !== null) {
            if (! $schedule->coversMenu($menuId)) {
                return response()->json([
                    'message' => 'Submenu ini tidak termasuk dalam cakupan jadwal QA.',
                ], 403);
            }
            $query->where('menu_id', $menuId);
        }

        $testCases = $query->get();

        // Hasil eksekusi terbaru per test case DI DALAM jadwal ini saja.
        $latestExecutions = $this->latestScheduleExecutions($schedule, $coveredIds)
            ->keyBy('test_case_id');

        $data = $testCases->map(function (TestCase $tc) use ($latestExecutions) {
            $execution = $latestExecutions->get($tc->id);

            return [
                'id' => $tc->id,
                'test_case_code' => $tc->test_case_code,
                'title' => $tc->title,
                'priority' => $tc->priority,
                'description' => $tc->description,
                'expected_result' => $tc->expected_result,
                'menu_id' => $tc->menu_id,
                'assigned_tester' => $tc->assignedTester ? [
                    'id' => $tc->assignedTester->id,
                    'name' => $tc->assignedTester->name,
                ] : null,
                'schedule_execution' => $execution ? [
                    'id' => $execution->id,
                    'status' => $execution->status,
                    'actual_result' => $execution->actual_result,
                    'notes' => $execution->notes,
                    'feature_description' => $execution->feature_description ?? null,
                    'executed_at' => $execution->executed_at,
                    'tester' => $execution->tester_name,
                ] : null,
            ];
        });

        return response()->json(['test_cases' => $data]);
    }

    /**
     * Feed aktivitas jadwal: log aktivitas QA + seluruh eksekusi test case
     * yang tercatat pada jadwal ini, digabung dan diurutkan terbaru.
     */
    public function activities(Request $request, QaSchedule $schedule): JsonResponse
    {
        $this->authorizeScheduleAccess($request, $schedule);

        $executionIds = TestExecution::query()
            ->where('qa_schedule_id', $schedule->id)
            ->pluck('id');

        $logs = ActivityLog::query()
            ->with('user:id,name')
            ->where(function ($query) use ($schedule, $executionIds): void {
                $query->where(fn ($q) => $q
                    ->where('entity_type', 'QaSchedule')
                    ->where('entity_id', $schedule->id))
                    ->orWhere(fn ($q) => $q
                        ->where('entity_type', 'TestExecution')
                        ->whereIn('entity_id', $executionIds->isEmpty() ? [0] : $executionIds));
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'type' => 'log',
                'at' => $log->created_at?->toISOString(),
                'user' => $log->user?->name,
                'action' => $log->action,
                'description' => $log->description,
                'result' => $log->result,
            ]);

        $executions = TestExecution::query()
            ->where('qa_schedule_id', $schedule->id)
            ->with(['tester:id,name', 'testCase:id,test_case_code,title,menu_id', 'testCase.menu:id,name'])
            ->orderByDesc('executed_at')
            ->limit(100)
            ->get()
            ->map(fn (TestExecution $execution) => [
                'type' => 'execution',
                'at' => $execution->executed_at?->toISOString(),
                'user' => $execution->tester?->name,
                'action' => 'test_executed',
                'description' => "Eksekusi {$execution->testCase?->test_case_code}: {$execution->testCase?->title}",
                'submenu' => $execution->testCase?->menu?->name,
                'test_case' => $execution->testCase?->test_case_code,
                'result' => $execution->status,
            ]);

        $activities = $logs->concat($executions)
            ->sortByDesc('at')
            ->take(100)
            ->values();

        return response()->json(['activities' => $activities]);
    }

    /**
     * Sinkronkan daftar test case eksplisit pada jadwal (pengurangan cakupan
     * oleh admin). Harus berada dalam cakupan submenu jadwal.
     */
    public function syncTestCases(Request $request, QaSchedule $schedule): JsonResponse
    {
        $this->authorizeScheduleAccess($request, $schedule);

        $validated = $request->validate([
            'test_case_ids' => ['present', 'array'],
            'test_case_ids.*' => ['integer', 'exists:test_cases,id'],
        ]);

        $testCaseIds = array_values(array_unique(array_map('intval', $validated['test_case_ids'])));
        $this->assertTestCasesWithinCoverage($testCaseIds, $schedule->module_id !== null ? (int) $schedule->module_id : null, $schedule->coveredMenuIds());

        DB::transaction(function () use ($schedule, $testCaseIds): void {
            $schedule->testCases()->sync($testCaseIds);
        });

        ActivityLog::record(
            'schedule_test_cases_updated',
            'Jadwal QA',
            "Cakupan test case jadwal {$schedule->schedule_code} diperbarui (".count($testCaseIds).' test case).',
            'QaSchedule',
            $schedule->id,
            null,
            ['test_case_ids' => $testCaseIds]
        );

        return response()->json([
            'message' => 'Cakupan test case jadwal berhasil diperbarui.',
            'test_cases_count' => count($testCaseIds),
        ]);
    }

    private function authorizeScheduleAccess(Request $request, QaSchedule $schedule): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $schedule->isAssignedTo($user->id), 403, 'Anda tidak ditugaskan pada jadwal QA ini.');
    }

    /**
     * @return Collection<int, object>
     */
    private function latestScheduleExecutions(QaSchedule $schedule, $testCaseIds)
    {
        if ($testCaseIds->isEmpty()) {
            return collect();
        }

        $latestDates = TestExecution::query()
            ->where('qa_schedule_id', $schedule->id)
            ->whereIn('test_case_id', $testCaseIds)
            ->select('test_case_id')
            ->selectRaw('MAX(executed_at) as max_date')
            ->groupBy('test_case_id');

        return DB::table('test_executions as te')
            ->joinSub($latestDates, 'latest', function ($join) {
                $join->on('te.test_case_id', '=', 'latest.test_case_id')
                    ->on('te.executed_at', '=', 'latest.max_date');
            })
            ->leftJoin('users as u', 'u.id', '=', 'te.tester_id')
            ->where('te.qa_schedule_id', $schedule->id)
            ->select('te.id', 'te.test_case_id', 'te.status', 'te.actual_result', 'te.notes', 'te.feature_description', 'te.executed_at', 'u.name as tester_name')
            ->get();
    }

    /**
     * Statistik cakupan per submenu untuk jadwal ini.
     * Termasuk bucket level modul (menu_id null) bila ada test case-nya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function menusCoverage(QaSchedule $schedule): array
    {
        $coveredIds = $schedule->coveredTestCaseIds();
        $testCases = TestCase::query()
            ->whereIn('id', $coveredIds)
            ->get(['id', 'menu_id', 'module_id']);

        $latestExecutions = $this->latestScheduleExecutions($schedule, $coveredIds)
            ->keyBy('test_case_id');

        $menuIds = $schedule->coveredMenuIds();
        $menus = $menuIds !== []
            ? ApplicationMenu::query()->whereIn('id', $menuIds)->orderBy('order_index')->get()->keyBy('id')
            : collect();

        $statsByMenu = [];
        foreach ($testCases as $tc) {
            $key = $tc->menu_id ?? 0;
            $statsByMenu[$key] = array_merge($this->emptyMenuStats(), $statsByMenu[$key] ?? []);
            $statsByMenu[$key]['total']++;
            $execution = $latestExecutions->get($tc->id);
            if ($execution === null) {
                $statsByMenu[$key]['untested']++;
            } else {
                $statusKey = strtolower(str_replace(' ', '_', $execution->status));
                $statsByMenu[$key][$statusKey]++;
            }
        }

        $result = [];
        foreach ($menuIds as $menuId) {
            $menu = $menus->get($menuId);
            if ($menu === null) {
                continue;
            }
            $stats = array_merge($this->emptyMenuStats(), $statsByMenu[$menuId] ?? []);
            $result[] = [
                'menu_id' => $menu->id,
                'name' => $menu->name,
                'code' => $menu->code,
                'is_active' => (bool) $menu->is_active,
                ...$stats,
                'percentage' => $this->percentage($stats),
            ];
        }

        if (isset($statsByMenu[0])) {
            $stats = array_merge($this->emptyMenuStats(), $statsByMenu[0]);
            $result[] = [
                'menu_id' => 0,
                'name' => 'Lainnya (Level Modul)',
                'code' => null,
                'is_active' => true,
                ...$stats,
                'percentage' => $this->percentage($stats),
            ];
        }

        return $result;
    }

    /**
     * @return array{total: int, pass: int, failed: int, pending: int, not_tested: int, untested: int}
     */
    private function emptyMenuStats(): array
    {
        return [
            'total' => 0,
            'pass' => 0,
            'failed' => 0,
            'pending' => 0,
            'not_tested' => 0,
            'untested' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function percentage(array $stats): int
    {
        $executed = ($stats['pass'] ?? 0) + ($stats['failed'] ?? 0) + ($stats['pending'] ?? 0) + ($stats['not_tested'] ?? 0);

        return ($stats['total'] ?? 0) > 0 ? (int) round(($executed / $stats['total']) * 100) : 0;
    }

    /**
     * Resolve daftar ID submenu tercakup. Mengembalikan null bila cakupan
     * tidak berubah (update payload lama) agar pivot tidak tertimpa.
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, int>|null
     */
    private function resolveMenuIds(Request $request, array $validated, ?QaSchedule $schedule = null): ?array
    {
        $moduleId = $this->effectiveModuleId($request, $validated, $schedule);
        $menuScope = $validated['menu_scope'] ?? $request->input('menu_scope') ?? QaSchedule::MENU_SCOPE_SELECTED;

        if (isset($validated['menu_ids'])) {
            return array_values(array_unique(array_map('intval', $validated['menu_ids'])));
        }

        if ($menuScope === QaSchedule::MENU_SCOPE_ALL) {
            return $moduleId === null ? [] : ApplicationMenu::query()
                ->where('module_id', $moduleId)
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (isset($validated['menu_id'])) {
            return [(int) $validated['menu_id']];
        }

        if ($schedule !== null) {
            $existing = $schedule->menus->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($existing !== []) {
                return $existing;
            }

            return $schedule->menu_id !== null ? [(int) $schedule->menu_id] : [];
        }

        return [];
    }

    /**
     * Resolve daftar ID tester tertugaskan (pivot multi-user).
     *
     * @param  array<string, mixed>  $validated
     * @return array<int, int>|null
     */
    private function resolveTesterIds(Request $request, array $validated, ?QaSchedule $schedule = null): ?array
    {
        if (isset($validated['tester_ids'])) {
            return array_values(array_unique(array_map('intval', $validated['tester_ids'])));
        }

        if (isset($validated['tester_id'])) {
            return [(int) $validated['tester_id']];
        }

        if ($schedule !== null) {
            $ids = $schedule->testers->pluck('id')->map(fn ($id) => (int) $id)->all();
            if ($schedule->tester_id !== null) {
                $ids[] = (int) $schedule->tester_id;
            }

            return array_values(array_unique($ids));
        }

        return [];
    }

    /**
     * Resolve daftar ID test case tercakup.
     * null = tidak berubah (update payload lama tanpa field terkait).
     *
     * @param  array<string, mixed>  $validated
     * @param  array<int, int>|null  $menuIds  null bila cakupan submenu tidak berubah
     * @return array<int, int>|null
     */
    private function resolveTestCaseIds(Request $request, array $validated, ?QaSchedule $schedule, ?array $menuIds): ?array
    {
        $moduleId = $this->effectiveModuleId($request, $validated, $schedule);

        if ($request->has('test_case_ids')) {
            $testCaseIds = array_values(array_unique(array_map('intval', $validated['test_case_ids'] ?? [])));

            if ($testCaseIds !== []) {
                $effectiveMenuIds = $menuIds ?? $schedule?->coveredMenuIds() ?? [];
                $this->assertTestCasesWithinCoverage($testCaseIds, $moduleId, $effectiveMenuIds);
            }

            return $testCaseIds;
        }

        if ($menuIds === null) {
            // Cakupan submenu tidak berubah → pertahankan pivot test case.
            return $schedule === null
                ? $this->autoCoveredTestCaseIds($moduleId, [])
                : null;
        }

        return $this->autoCoveredTestCaseIds($moduleId, $menuIds);
    }

    private function effectiveModuleId(Request $request, array $validated, ?QaSchedule $schedule): ?int
    {
        $moduleId = $validated['module_id'] ?? $request->input('module_id') ?? $schedule?->module_id;

        return $moduleId === null ? null : (int) $moduleId;
    }

    /**
     * Pastikan setiap test case eksplisit berada dalam cakupan submenu/modul jadwal.
     *
     * @param  array<int, int>  $testCaseIds
     * @param  array<int, int>  $menuIds
     */
    private function assertTestCasesWithinCoverage(array $testCaseIds, ?int $moduleId, array $menuIds): void
    {
        $covered = TestCase::query()
            ->whereIn('id', $testCaseIds)
            ->where(function ($query) use ($moduleId, $menuIds): void {
                if ($menuIds !== []) {
                    $query->whereIn('menu_id', $menuIds);
                }

                if ($moduleId !== null) {
                    $query->orWhere(function ($moduleQuery) use ($moduleId): void {
                        $moduleQuery->where('module_id', $moduleId)->whereNull('menu_id');
                    });
                }
            })
            ->count();

        if ($covered !== count($testCaseIds)) {
            throw ValidationException::withMessages([
                'test_case_ids' => ['Sebagian test case berada di luar cakupan submenu jadwal.'],
            ]);
        }
    }

    /**
     * Test case aktif yang otomatis tercakup oleh submenu/modul jadwal.
     *
     * @param  array<int, int>  $menuIds
     * @return array<int, int>
     */
    private function autoCoveredTestCaseIds(?int $moduleId, array $menuIds): array
    {
        return TestCase::query()
            ->where('is_active', true)
            ->where(function ($query) use ($moduleId, $menuIds): void {
                if ($menuIds !== []) {
                    $query->whereIn('menu_id', $menuIds);
                }

                if ($moduleId !== null) {
                    $query->orWhere(function ($moduleQuery) use ($moduleId): void {
                        $moduleQuery->where('module_id', $moduleId)->whereNull('menu_id');
                    });
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduleRules(Request $request, ?QaSchedule $schedule = null): array
    {
        $moduleId = $request->input('module_id');
        $menuScope = $request->input('menu_scope', QaSchedule::MENU_SCOPE_SELECTED);
        // Payload lama tanpa field cakupan tetap valid (kompatibilitas mundur).
        $usesCoverageFields = $request->has('menu_scope') || $request->has('menu_ids') || $request->has('tester_ids');

        $rules = [
            'title' => 'required|string|max:255',
            ...$this->masterDataValidationRules($request),
            'menu_scope' => ['nullable', Rule::in([QaSchedule::MENU_SCOPE_ALL, QaSchedule::MENU_SCOPE_SELECTED])],
            'menu_ids' => [
                Rule::requiredIf($usesCoverageFields && $menuScope === QaSchedule::MENU_SCOPE_SELECTED && ! $request->filled('menu_id')),
                'nullable',
                'array',
                'min:1',
            ],
            'menu_ids.*' => [
                'integer',
                Rule::exists('application_menus', 'id')
                    ->where(fn ($query) => $query
                        ->where('application_id', $request->input('application_id'))
                        ->where('module_id', $moduleId)
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'testing_type' => ['required', Rule::in(['Daily', 'Weekly', 'Regression', 'Retesting', 'Release', 'Smoke Testing'])],
            'tester_id' => 'nullable|exists:users,id',
            'tester_ids' => 'nullable|array',
            'tester_ids.*' => 'exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'target_test_cases_count' => 'nullable|integer|min:0',
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'notes' => 'nullable|string',
            'status' => [$schedule ? 'required' : 'nullable', Rule::in(['Scheduled', 'In Progress', 'Completed', 'Overdue', 'Cancelled'])],
            'test_case_ids' => 'nullable|array',
            'test_case_ids.*' => 'exists:test_cases,id',
        ];

        // Jadwal terpusat mensyaratkan modul; cakupan "semua submenu" juga.
        $rules['module_id'][] = Rule::requiredIf($menuScope === QaSchedule::MENU_SCOPE_ALL || $request->filled('menu_ids'));

        return $rules;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function masterDataValidationRules(Request $request): array
    {
        return [
            'application_id' => [
                'required',
                'integer',
                Rule::exists('applications', 'id')
                    ->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'module_id' => [
                'nullable',
                'integer',
                Rule::exists('application_modules', 'id')
                    ->where(fn ($query) => $query
                        ->where('application_id', $request->input('application_id'))
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
            'menu_id' => [
                'nullable',
                'integer',
                Rule::exists('application_menus', 'id')
                    ->where(fn ($query) => $query
                        ->where('application_id', $request->input('application_id'))
                        ->where('module_id', $request->input('module_id'))
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],
        ];
    }
}
