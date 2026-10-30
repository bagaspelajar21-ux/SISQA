<?php

namespace App\Models;

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
