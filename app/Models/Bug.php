<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bug extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bug_code',
        'title',
        'application_id',
        'module_id',
        'menu_id',
        'test_case_id',
        'qa_schedule_id',
        'application_flow_id',
        'application_flow_step_id',
        'description',
        'steps_to_reproduce',
        'expected_result',
        'actual_result',
        'reporter_id',
        'assignee_id',
        'report_date',
        'severity',
        'priority',
        'status',
        'resolution_notes',
        'duplicate_of_id',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    #[Scope]
    protected function inQaDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        $applyActivityDateRange = static function (Builder $query) use ($startDate, $endDate): void {
            if ($startDate) {
                $query->whereDate('report_date', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('report_date', '<=', $endDate);
            }
        };

        $query->where(function (Builder $query) use ($startDate, $endDate, $applyActivityDateRange): void {
            $query->whereHas('schedule', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate))
                ->orWhereHas('testCase.schedules', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate))
                ->orWhere(function (Builder $unscheduledQuery) use ($applyActivityDateRange): void {
                    $unscheduledQuery->whereNull('qa_schedule_id')
                        ->whereDoesntHave('testCase.schedules');
                    $applyActivityDateRange($unscheduledQuery);
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

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function schedule()
    {
        return $this->belongsTo(QaSchedule::class, 'qa_schedule_id');
    }

    public function flow()
    {
        return $this->belongsTo(ApplicationFlow::class, 'application_flow_id');
    }

    public function flowStep()
    {
        return $this->belongsTo(ApplicationFlowStep::class, 'application_flow_step_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function duplicateOf()
    {
        return $this->belongsTo(Bug::class, 'duplicate_of_id');
    }

    public function duplicates()
    {
        return $this->hasMany(Bug::class, 'duplicate_of_id');
    }

    public function histories()
    {
        return $this->hasMany(BugHistory::class)->orderBy('created_at', 'desc');
    }

    public function evidences()
    {
        return $this->hasMany(BugEvidence::class);
    }

    public function retests()
    {
        return $this->hasMany(Retest::class)->orderBy('created_at', 'desc');
    }
}
