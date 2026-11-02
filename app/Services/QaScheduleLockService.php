<?php

namespace App\Services;

use App\Models\Bug;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QaScheduleLockService
{
    public function expiredSchedule(?QaSchedule $schedule): ?QaSchedule
    {
        return $schedule?->status !== 'Cancelled' && $schedule?->hasPassedEndTime() ? $schedule : null;
    }

    public function expiredScheduleFromSchedules(Collection $schedules): ?QaSchedule
    {
        $schedules = $schedules->filter(fn (QaSchedule $schedule): bool => $schedule->status !== 'Cancelled');

        if ($schedules->contains(
            fn (QaSchedule $schedule): bool => $schedule->end_time !== null && now()->lessThanOrEqualTo($schedule->end_time)
        )) {
            return null;
        }

        return $schedules
            ->filter(fn (QaSchedule $schedule): bool => $schedule->hasPassedEndTime())
            ->sortByDesc('end_time')
            ->first();
    }

    public function expiredScheduleForTestCase(TestCase $testCase): ?QaSchedule
    {
        return $this->expiredScheduleForContext(
            null,
            $testCase->id,
            $testCase->module_id,
            $testCase->menu_id,
        );
    }

    public function expiredScheduleForBug(Bug $bug): ?QaSchedule
    {
        return $this->expiredScheduleForContext(
            $bug->qa_schedule_id,
            $bug->test_case_id,
            $bug->module_id,
            $bug->menu_id,
        );
    }

    public function expiredScheduleForContext(
        ?int $scheduleId,
        ?int $testCaseId,
        ?int $moduleId = null,
        ?int $menuId = null,
    ): ?QaSchedule {
        if ($scheduleId === null && $testCaseId === null && $moduleId === null && $menuId === null) {
            return null;
        }

        if ($testCaseId === null && $moduleId === null && $menuId === null) {
            return $this->expiredSchedule(QaSchedule::query()->find($scheduleId));
        }

        $testCase = $testCaseId === null
            ? null
            : TestCase::query()->select(['module_id', 'menu_id'])->find($testCaseId);

        $expiredSchedules = [];

        if ($testCaseId !== null) {
            $expiredSchedules[] = $this->expiredScheduleForScope(
                fn (Builder $query) => $query->whereHas(
                    'testCases',
                    fn (Builder $testCases) => $testCases->whereKey($testCaseId),
                ),
            );
        }

        $moduleIds = array_unique(array_map('intval', array_filter([$moduleId, $testCase?->module_id])));
        foreach ($moduleIds as $contextModuleId) {
            $expiredSchedules[] = $this->expiredScheduleForScope(
                fn (Builder $query) => $query->where('module_id', $contextModuleId),
            );
        }

        $menuIds = array_unique(array_map('intval', array_filter([$menuId, $testCase?->menu_id])));
        foreach ($menuIds as $contextMenuId) {
            $expiredSchedules[] = $this->expiredScheduleForScope(
                fn (Builder $query) => $query->where('menu_id', $contextMenuId),
            );
        }

        if ($scheduleId !== null) {
            $schedule = QaSchedule::query()->find($scheduleId);
            $scheduleMatchesContext = $schedule !== null
                && (
                    ($schedule->module_id !== null && in_array((int) $schedule->module_id, $moduleIds, true))
                    || ($schedule->menu_id !== null && in_array((int) $schedule->menu_id, $menuIds, true))
                    || ($testCaseId !== null && $schedule->testCases()->whereKey($testCaseId)->exists())
                );

            if (! $scheduleMatchesContext) {
                $expiredSchedules[] = $this->expiredSchedule($schedule);
            }
        }

        return collect($expiredSchedules)
            ->filter()
            ->sortByDesc('end_time')
            ->first();
    }

    public function excludeLockedTestCases(Builder $query): void
    {
        $query->whereNotIn('id', $this->lockedTestCasesQuery()->select('test_cases.id'));
    }

    public function excludeLockedBugs(Builder $query): void
    {
        $query->whereNotIn('id', $this->lockedBugsQuery()->select('bugs.id'));
    }

    public function excludeLockedRetests(Builder $query): void
    {
        $query->whereNotIn('id', $this->lockedRetestsQuery()->select('retests.id'));
    }

    public function expiredScheduleForModuleMenu(?int $moduleId, ?int $menuId): ?QaSchedule
    {
        if ($moduleId === null && $menuId === null) {
            return null;
        }

        return $this->expiredScheduleForContext(null, null, $moduleId, $menuId);
    }

    private function lockedTestCasesQuery(): Builder
    {
        return TestCase::query()
            ->where(function (Builder $query): void {
                $query->whereHas('schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules));
            })
            ->whereDoesntHave('schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules));
    }

    private function lockedBugsQuery(): Builder
    {
        return Bug::query()
            ->where(function (Builder $query): void {
                $query->whereHas('schedule', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules));
            })
            ->whereDoesntHave('schedule', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules));
    }

    private function lockedRetestsQuery(): Builder
    {
        return Retest::query()
            ->where(function (Builder $query): void {
                $query->whereHas('schedule', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.schedule', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.testCase.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.testCase.module.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('bug.testCase.menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules));
            })
            ->whereDoesntHave('schedule', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.schedule', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.testCase.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.testCase.module.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('bug.testCase.menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules));
    }

    private function whereExpired(Builder $query): void
    {
        $query->where('status', '!=', 'Cancelled')
            ->where('end_time', '<', now());
    }

    private function whereActive(Builder $query): void
    {
        $query->where('status', '!=', 'Cancelled')
            ->where('end_time', '>=', now());
    }

    private function expiredScheduleForScope(Closure $scope): ?QaSchedule
    {
        $schedules = QaSchedule::query()
            ->where('status', '!=', 'Cancelled')
            ->where($scope);

        if ((clone $schedules)->where('end_time', '>=', now())->exists()) {
            return null;
        }

        return (clone $schedules)
            ->where('end_time', '<', now())
            ->orderByDesc('end_time')
            ->first();
    }
}
