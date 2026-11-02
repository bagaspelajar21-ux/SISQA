<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'test_case_code',
        'title',
        'application_id',
        'module_id',
        'menu_id',
        'application_flow_id',
        'application_flow_step_id',
        'description',
        'preconditions',
        'test_steps',
        'test_data',
        'expected_result',
        'assigned_tester_id',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    #[Scope]
    protected function inQaDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        $applyActivityDateRange = static function (Builder $query, string $column) use ($startDate, $endDate): void {
            if ($startDate) {
                $query->whereDate($column, '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate($column, '<=', $endDate);
            }
        };

        $query->where(function (Builder $query) use ($startDate, $endDate, $applyActivityDateRange): void {
            $query->whereHas('schedules', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate))
                ->orWhereHas('executions', function (Builder $executionQuery) use ($startDate, $endDate): void {
                    $executionQuery->whereHas('schedule', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate));
                })
                ->orWhere(function (Builder $unscheduledQuery) use ($applyActivityDateRange): void {
                    $unscheduledQuery->whereDoesntHave('schedules')
                        ->whereDoesntHave('executions.schedule')
                        ->where(function (Builder $activityQuery) use ($applyActivityDateRange): void {
                            $activityQuery->whereHas('executions', fn (Builder $executionQuery) => $applyActivityDateRange($executionQuery, 'executed_at'))
                                ->orWhere(function (Builder $neverExecutedQuery) use ($applyActivityDateRange): void {
                                    $neverExecutedQuery->whereDoesntHave('executions');
                                    $applyActivityDateRange($neverExecutedQuery, 'test_cases.created_at');
                                });
                        });
                });
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

    public function flow()
    {
        return $this->belongsTo(ApplicationFlow::class, 'application_flow_id');
    }

    public function flowStep()
    {
        return $this->belongsTo(ApplicationFlowStep::class, 'application_flow_step_id');
    }

    public function assignedTester()
    {
        return $this->belongsTo(User::class, 'assigned_tester_id');
    }

    public function schedules()
    {
        return $this->belongsToMany(QaSchedule::class, 'qa_schedule_test_cases');
    }

    public function executions()
    {
        return $this->hasMany(TestExecution::class)->orderBy('executed_at', 'desc');
    }

    public function latestExecution()
    {
        return $this->hasOne(TestExecution::class)->latestOfMany('executed_at');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class);
    }
}
