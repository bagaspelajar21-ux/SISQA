<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QaSchedule extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_SCOPE_ALL = 'all';

    public const MENU_SCOPE_SELECTED = 'selected';

    protected $fillable = [
        'schedule_code',
        'title',
        'application_id',
        'module_id',
        'menu_id',
        'menu_scope',
        'application_flow_id',
        'testing_type',
        'tester_id',
        'start_time',
        'end_time',
        'target_test_cases_count',
        'priority',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'start_notification_sent_at' => 'datetime',
        'end_notification_sent_at' => 'datetime',
        'target_test_cases_count' => 'integer',
    ];

    public function hasPassedEndTime(): bool
    {
        return $this->end_time !== null && now()->greaterThan($this->end_time);
    }

    #[Scope]
    protected function overlappingDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate) {
            $query->whereDate('end_time', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('start_time', '<=', $endDate);
        }
    }

    #[Scope]
    protected function assignedTo(Builder $query, int $userId): void
    {
        $query->where(function (Builder $query) use ($userId): void {
            $query->where('tester_id', $userId)
                ->orWhereHas('testers', fn (Builder $testerQuery) => $testerQuery->whereKey($userId));
        });
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function module()
    {
        return $this->belongsTo(ApplicationModule::class, 'module_id');
    }

    public function menu()
    {
        return $this->belongsTo(ApplicationMenu::class, 'menu_id');
    }

    public function menus()
    {
        return $this->belongsToMany(ApplicationMenu::class, 'qa_schedule_menus', 'qa_schedule_id', 'menu_id')
            ->withPivot('id')
            ->orderBy('application_menus.order_index');
    }

    public function flow()
    {
        return $this->belongsTo(ApplicationFlow::class, 'application_flow_id');
    }

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function testers()
    {
        return $this->belongsToMany(User::class, 'qa_schedule_testers', 'qa_schedule_id', 'user_id')
            ->withPivot('id')
            ->orderBy('users.name');
    }

    /**
     * Semua user yang ditugaskan pada jadwal ini.
     * tester_id lama tetap dihitung agar jadwal warisan tetap dikenali.
     *
     * @return Collection<int, int>
     */
    public function assignedUserIds()
    {
        return $this->testers->pluck('id')
            ->when($this->tester_id !== null, fn ($ids) => $ids->push($this->tester_id))
            ->unique()
            ->values();
    }

    public function isAssignedTo(int $userId): bool
    {
        if ($this->tester_id !== null && (int) $this->tester_id === $userId) {
            return true;
        }

        return $this->testers->contains('id', $userId);
    }

    public function testCases()
    {
        return $this->belongsToMany(TestCase::class, 'qa_schedule_test_cases');
    }

    public function testExecutions()
    {
        return $this->hasMany(TestExecution::class, 'qa_schedule_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'qa_schedule_id');
    }

    public function retests()
    {
        return $this->hasMany(Retest::class, 'qa_schedule_id');
    }

    /**
     * ID submenu yang tercakup jadwal ini.
     * Jadwal baru menyimpan snapshot di qa_schedule_menus; jadwal lama
     * jatuh kembali ke kolom menu_id / seluruh submenu modul.
     *
     * @return array<int, int>
     */
    public function coveredMenuIds(): array
    {
        $ids = $this->menus->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids !== []) {
            return $ids;
        }

        if ($this->menu_id !== null) {
            return [(int) $this->menu_id];
        }

        if ($this->module_id !== null) {
            return ApplicationMenu::query()
                ->where('module_id', $this->module_id)
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return [];
    }

    /**
     * Apakah jadwal ini mencakup submenu tertentu (snapshot pivot,
     * kolom menu_id warisan, atau cakupan seluruh modul warisan).
     */
    public function coversMenu(int $menuId): bool
    {
        return in_array($menuId, $this->coveredMenuIds(), true);
    }

    /**
     * Test case yang tercakup jadwal ini.
     * Prioritas: pivot qa_schedule_test_cases (snapshot final).
     * Fallback warisan: test case pada submenu cakupan atau level modul.
     *
     * @return Collection<int, int>
     */
    public function coveredTestCaseIds()
    {
        $pivotIds = $this->testCases()->pluck('test_cases.id');

        if ($pivotIds->isNotEmpty()) {
            return $pivotIds->map(fn ($id) => (int) $id)->values();
        }

        $menuIds = $this->coveredMenuIds();

        return TestCase::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($menuIds): void {
                if ($menuIds !== []) {
                    $query->whereIn('menu_id', $menuIds);
                }

                if ($this->module_id !== null) {
                    $query->orWhere(function (Builder $moduleQuery): void {
                        $moduleQuery->where('module_id', $this->module_id)
                            ->whereNull('menu_id');
                    });
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Hitung progres berdasarkan 1 hasil eksekusi terakhir per test case
     * dalam jadwal ini. Seluruh riwayat eksekusi tetap tersimpan.
     *
     * @return array{target: int, completed: int, remaining: int, percentage: int, pass: int, failed: int, pending: int, not_tested: int, untested: int}
     */
    public function getProgressAttribute(): array
    {
        $testCaseIds = $this->coveredTestCaseIds();
        $target = $testCaseIds->count();

        $latestExecutions = collect();
        if ($target > 0) {
            $latestDates = TestExecution::query()
                ->where('qa_schedule_id', $this->id)
                ->whereIn('test_case_id', $testCaseIds)
                ->select('test_case_id')
                ->selectRaw('MAX(executed_at) as max_date')
                ->groupBy('test_case_id');

            $latestExecutions = DB::table('test_executions as te')
                ->joinSub($latestDates, 'latest', function ($join) {
                    $join->on('te.test_case_id', '=', 'latest.test_case_id')
                        ->on('te.executed_at', '=', 'latest.max_date');
                })
                ->where('te.qa_schedule_id', $this->id)
                ->select('te.test_case_id', 'te.status')
                ->get();
        }

        $counts = [
            'PASS' => 0,
            'FAILED' => 0,
            'PENDING' => 0,
            'NOT TESTED' => 0,
        ];

        foreach ($latestExecutions as $execution) {
            if (isset($counts[$execution->status])) {
                $counts[$execution->status]++;
            }
        }

        $completed = $latestExecutions->count();

        return [
            'target' => $target,
            'completed' => $completed,
            'remaining' => max(0, $target - $completed),
            'percentage' => $target > 0 ? min(100, (int) round(($completed / $target) * 100)) : 0,
            'pass' => $counts['PASS'],
            'failed' => $counts['FAILED'],
            'pending' => $counts['PENDING'],
            'not_tested' => $counts['NOT TESTED'],
            'untested' => max(0, $target - $completed),
        ];
    }
}
