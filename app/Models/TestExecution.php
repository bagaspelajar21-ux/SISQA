<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_case_id',
        'qa_schedule_id',
        'tester_id',
        'status',
        'actual_result',
        'notes',
        'executed_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    #[Scope]
    protected function inQaDateRange(Builder $query, ?string $startDate, ?string $endDate): void
    {
        $query->where(function (Builder $query) use ($startDate, $endDate): void {
            $query->whereHas('schedule', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate))
                ->orWhereHas('testCase.schedules', fn (Builder $scheduleQuery) => $scheduleQuery->overlappingDateRange($startDate, $endDate))
                ->orWhere(function (Builder $unscheduledQuery) use ($startDate, $endDate): void {
                    $unscheduledQuery->whereNull('qa_schedule_id')
                        ->whereDoesntHave('testCase.schedules');

                    if ($startDate) {
                        $unscheduledQuery->whereDate('executed_at', '>=', $startDate);
                    }

                    if ($endDate) {
                        $unscheduledQuery->whereDate('executed_at', '<=', $endDate);
                    }
                });
        });
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function schedule()
    {
        return $this->belongsTo(QaSchedule::class, 'qa_schedule_id');
    }

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function evidences()
    {
        return $this->hasMany(TestExecutionEvidence::class);
    }
}
