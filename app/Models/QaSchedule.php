<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QaSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'schedule_code',
        'title',
        'application_id',
        'module_id',
        'menu_id',
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
        'target_test_cases_count' => 'integer',
    ];

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

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
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
     * Compute actual progress percentage from test executions
     */
    public function getProgressAttribute(): array
    {
        $target = $this->target_test_cases_count ?: $this->testCases()->count();
        $completedExecutions = $this->testExecutions()->whereIn('status', ['PASS', 'FAILED'])->count();
        $percentage = $target > 0 ? min(100, round(($completedExecutions / $target) * 100)) : 0;

        return [
            'target' => $target,
            'completed' => $completedExecutions,
            'remaining' => max(0, $target - $completedExecutions),
            'percentage' => $percentage,
        ];
    }
}
