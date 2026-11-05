<?php

namespace App\Services;

use App\Models\AutomatedReminderSetting;
use App\Models\Bug;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class QaScheduleLockService
{
    private ?AutomatedReminderSetting $currentSettings = null;

    public function expiredSchedule(?QaSchedule $schedule): ?QaSchedule
    {
        return $this->isScheduleLocked($schedule) ? $schedule : null;
    }

    public function expiredScheduleFromSchedules(Collection $schedules): ?QaSchedule
    {
        $schedules = $schedules->filter(fn (QaSchedule $schedule): bool => $schedule->status !== 'Cancelled');

        if ($schedules->contains(
            fn (QaSchedule $schedule): bool => $this->isScheduleInAccessPeriod($schedule)
        )) {
            return null;
        }

        return $schedules
            ->filter(fn (QaSchedule $schedule): bool => $this->isScheduleLocked($schedule))
            ->sortByDesc('end_time')
            ->first();
    }

    public function lockMessage(QaSchedule $schedule): string
    {
        $settings = $this->settings();
        $endedAt = $schedule->end_time?->format('d/m/Y H:i');

        if ($settings->qa_lock_mode === 'lock_for_duration') {
            return "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$endedAt}. Penguncian sementara berlangsung selama {$settings->qa_lock_minutes} menit setelah jadwal berakhir.";
        }

        if ($settings->qa_lock_mode === 'follow_schedule') {
            return "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$endedAt}. Akses dibuka otomatis saat jadwal QA berikutnya pada modul yang sama dimulai.";
        }

        return "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$endedAt}. Masa toleransi {$settings->qa_lock_minutes} menit telah habis; hubungi Admin jika perlu melanjutkan.";
    }

    /**
     * @return array{mode: string, minutes: int}
     */
    public function policySummary(): array
    {
        $settings = $this->settings();

        return [
            'mode' => $settings->qa_lock_mode,
            'minutes' => $settings->qa_lock_minutes,
        ];
    }

    /**
     * @return array{locked: bool, mode: string, schedule_code: ?string, unlock_at: ?string, waiting_for_schedule: bool}
     */
    public function statusForModule(int $moduleId, ?int $menuId = null): array
    {
        $settings = $this->settings();
        $lockedSchedule = $this->expiredScheduleForModuleMenu($moduleId, $menuId);
        $unlockAt = null;
        $waitingForSchedule = false;

        if ($lockedSchedule !== null && $settings->qa_lock_mode === 'lock_for_duration') {
            $unlockAt = $lockedSchedule->end_time
                ?->copy()
                ->addMinutes($settings->qa_lock_minutes)
                ->toIso8601String();
        } elseif ($lockedSchedule !== null) {
            $nextScheduleQuery = QaSchedule::query()
                ->whereIn('status', ['Scheduled', 'In Progress'])
                ->where('start_time', '>', now());

            if ($menuId !== null) {
                $nextScheduleQuery->where(function (Builder $query) use ($moduleId, $menuId): void {
                    $query->where(function (Builder $coverage) use ($menuId): void {
                        $coverage->where('menu_id', $menuId)
                            ->orWhereHas('menus', fn (Builder $menus) => $menus->whereKey($menuId));
                    })->orWhere(function (Builder $legacy) use ($moduleId): void {
                        $legacy->where('module_id', $moduleId)->whereDoesntHave('menus');
                    });
                });
            } else {
                $nextScheduleQuery->where('module_id', $moduleId);
            }

            $nextSchedule = $nextScheduleQuery->orderBy('start_time')->first(['start_time']);
            $unlockAt = $nextSchedule?->start_time?->toIso8601String();
            $waitingForSchedule = $unlockAt === null;
        }

        return [
            'locked' => $lockedSchedule !== null,
            'mode' => $settings->qa_lock_mode,
            'schedule_code' => $lockedSchedule?->schedule_code,
            'unlock_at' => $unlockAt,
            'waiting_for_schedule' => $waitingForSchedule,
        ];
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
                fn (Builder $query) => $query
                    ->where('module_id', $contextModuleId)
                    ->whereDoesntHave('menus'),
            );
        }

        $menuIds = array_unique(array_map('intval', array_filter([$menuId, $testCase?->menu_id])));
        foreach ($menuIds as $contextMenuId) {
            $expiredSchedules[] = $this->expiredScheduleForMenuCoverage($contextMenuId);
        }

        if ($scheduleId !== null) {
            $schedule = QaSchedule::query()->find($scheduleId);
            $scheduleMatchesContext = $schedule !== null
                && (
                    ($schedule->module_id !== null && in_array((int) $schedule->module_id, $moduleIds, true) && $schedule->menus->isEmpty())
                    || ($schedule->menu_id !== null && in_array((int) $schedule->menu_id, $menuIds, true))
                    || collect($menuIds)->contains(fn (int $contextMenuId): bool => $schedule->coversMenu($contextMenuId))
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
                    ->orWhereHas('module.schedules', function (Builder $schedules): void {
                        $this->whereExpired($schedules);
                        $schedules->whereDoesntHave('menus');
                    })
                    ->orWhereHas('menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('menu.qaSchedules', fn (Builder $schedules) => $this->whereExpired($schedules));
            })
            ->whereDoesntHave('schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('module.schedules', function (Builder $schedules): void {
                $this->whereActive($schedules);
                $schedules->whereDoesntHave('menus');
            })
            ->whereDoesntHave('menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('menu.qaSchedules', fn (Builder $schedules) => $this->whereActive($schedules));
    }

    private function lockedBugsQuery(): Builder
    {
        $legacyModuleSchedules = function (Builder $schedules): void {
            $this->whereExpired($schedules);
            $schedules->whereDoesntHave('menus');
        };
        $legacyModuleSchedulesActive = function (Builder $schedules): void {
            $this->whereActive($schedules);
            $schedules->whereDoesntHave('menus');
        };

        return Bug::query()
            ->where(function (Builder $query) use ($legacyModuleSchedules): void {
                $query->whereHas('schedule', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('menu.qaSchedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('testCase.menu.schedules', fn (Builder $schedules) => $this->whereExpired($schedules))
                    ->orWhereHas('testCase.menu.qaSchedules', fn (Builder $schedules) => $this->whereExpired($schedules));
            })
            ->whereDoesntHave('schedule', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('menu.qaSchedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('testCase.menu.schedules', fn (Builder $schedules) => $this->whereActive($schedules))
            ->whereDoesntHave('testCase.menu.qaSchedules', fn (Builder $schedules) => $this->whereActive($schedules));
    }

    private function lockedRetestsQuery(): Builder
    {
        $legacyModuleSchedules = function (Builder $schedules): void {
            $this->whereExpired($schedules);
            $schedules->whereDoesntHave('menus');
        };
        $legacyModuleSchedulesActive = function (Builder $schedules): void {
            $this->whereActive($schedules);
            $schedules->whereDoesntHave('menus');
        };
        $expiredSchedules = function (Builder $schedules): void {
            $this->whereExpired($schedules);
        };
        $activeSchedules = function (Builder $schedules): void {
            $this->whereActive($schedules);
        };

        return Retest::query()
            ->where(function (Builder $query) use ($legacyModuleSchedules, $expiredSchedules): void {
                $query->whereHas('schedule', $expiredSchedules)
                    ->orWhereHas('module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('menu.schedules', $expiredSchedules)
                    ->orWhereHas('menu.qaSchedules', $expiredSchedules)
                    ->orWhereHas('testCase.schedules', $expiredSchedules)
                    ->orWhereHas('testCase.module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('testCase.menu.schedules', $expiredSchedules)
                    ->orWhereHas('testCase.menu.qaSchedules', $expiredSchedules)
                    ->orWhereHas('bug.schedule', $expiredSchedules)
                    ->orWhereHas('bug.module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('bug.menu.schedules', $expiredSchedules)
                    ->orWhereHas('bug.menu.qaSchedules', $expiredSchedules)
                    ->orWhereHas('bug.testCase.schedules', $expiredSchedules)
                    ->orWhereHas('bug.testCase.module.schedules', $legacyModuleSchedules)
                    ->orWhereHas('bug.testCase.menu.schedules', $expiredSchedules)
                    ->orWhereHas('bug.testCase.menu.qaSchedules', $expiredSchedules);
            })
            ->whereDoesntHave('schedule', $activeSchedules)
            ->whereDoesntHave('module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('menu.schedules', $activeSchedules)
            ->whereDoesntHave('menu.qaSchedules', $activeSchedules)
            ->whereDoesntHave('testCase.schedules', $activeSchedules)
            ->whereDoesntHave('testCase.module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('testCase.menu.schedules', $activeSchedules)
            ->whereDoesntHave('testCase.menu.qaSchedules', $activeSchedules)
            ->whereDoesntHave('bug.schedule', $activeSchedules)
            ->whereDoesntHave('bug.module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('bug.menu.schedules', $activeSchedules)
            ->whereDoesntHave('bug.menu.qaSchedules', $activeSchedules)
            ->whereDoesntHave('bug.testCase.schedules', $activeSchedules)
            ->whereDoesntHave('bug.testCase.module.schedules', $legacyModuleSchedulesActive)
            ->whereDoesntHave('bug.testCase.menu.schedules', $activeSchedules)
            ->whereDoesntHave('bug.testCase.menu.qaSchedules', $activeSchedules);
    }

    private function whereExpired(Builder $query): void
    {
        $settings = $this->settings();
        $query->where('status', '!=', 'Cancelled');

        if ($settings->qa_lock_mode === 'follow_schedule') {
            $query->where('end_time', '<', now());

            return;
        }

        if ($settings->qa_lock_mode === 'lock_for_duration') {
            if ($settings->qa_lock_minutes === 0) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('end_time', '<', now())
                ->where('end_time', '>', now()->subMinutes($settings->qa_lock_minutes));

            return;
        }

        $query->where('end_time', '<', now()->subMinutes($settings->qa_lock_minutes));
    }

    private function whereActive(Builder $query): void
    {
        $settings = $this->settings();
        $query->where('status', '!=', 'Cancelled')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', $settings->qa_lock_mode === 'grace_then_lock'
                ? now()->subMinutes($settings->qa_lock_minutes)
                : now());
    }

    /**
     * Evaluasi penguncian untuk satu submenu berdasarkan seluruh jadwal
     * yang mencakup submenu tersebut — baik lewat kolom menu_id warisan
     * maupun snapshot pivot qa_schedule_menus.
     */
    private function expiredScheduleForMenuCoverage(int $menuId): ?QaSchedule
    {
        $schedules = QaSchedule::query()
            ->where('status', '!=', 'Cancelled')
            ->where(function (Builder $query) use ($menuId): void {
                $query->where('menu_id', $menuId)
                    ->orWhereHas('menus', fn (Builder $menus) => $menus->whereKey($menuId));
            });

        if ((clone $schedules)->where(fn (Builder $query) => $this->whereActive($query))->exists()) {
            return null;
        }

        return (clone $schedules)
            ->where(fn (Builder $query) => $this->whereExpired($query))
            ->orderByDesc('end_time')
            ->first();
    }

    private function expiredScheduleForScope(Closure $scope): ?QaSchedule
    {
        $schedules = QaSchedule::query()
            ->where('status', '!=', 'Cancelled')
            ->where($scope);

        if ((clone $schedules)->where(fn (Builder $query) => $this->whereActive($query))->exists()) {
            return null;
        }

        return (clone $schedules)
            ->where(fn (Builder $query) => $this->whereExpired($query))
            ->orderByDesc('end_time')
            ->first();
    }

    private function isScheduleLocked(?QaSchedule $schedule): bool
    {
        if ($schedule === null || $schedule->status === 'Cancelled' || $schedule->end_time === null) {
            return false;
        }

        $settings = $this->settings();
        $now = now();

        if ($settings->qa_lock_mode === 'lock_for_duration') {
            return $settings->qa_lock_minutes > 0
                && $now->greaterThan($schedule->end_time)
                && $now->lessThan($schedule->end_time->copy()->addMinutes($settings->qa_lock_minutes));
        }

        if ($settings->qa_lock_mode === 'follow_schedule') {
            return $now->greaterThan($schedule->end_time);
        }

        return $now->greaterThan($schedule->end_time->copy()->addMinutes($settings->qa_lock_minutes));
    }

    private function isScheduleInAccessPeriod(QaSchedule $schedule): bool
    {
        if ($schedule->status === 'Cancelled' || $schedule->end_time === null) {
            return false;
        }

        if ($schedule->start_time !== null && now()->lessThan($schedule->start_time)) {
            return false;
        }

        $settings = $this->settings();
        $accessEndsAt = $schedule->end_time->copy();

        if ($settings->qa_lock_mode === 'grace_then_lock') {
            $accessEndsAt->addMinutes($settings->qa_lock_minutes);
        }

        return now()->lessThanOrEqualTo($accessEndsAt);
    }

    private function settings(): AutomatedReminderSetting
    {
        return $this->currentSettings ??= AutomatedReminderSetting::current();
    }
}
