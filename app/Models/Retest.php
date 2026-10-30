<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retest extends Model
{
    use HasFactory;

    protected $fillable = [
        'bug_id',
        'test_case_id',
        'qa_schedule_id',
        'application_id',
        'module_id',
        'menu_id',
        'tester_id',
        'retest_date',
        'tested_version',
        'expected_result',
        'actual_result',
        'status', // Pending Retest, Passed, Failed, Reopened
        'notes',
    ];

    protected $casts = [
        'retest_date' => 'datetime',
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function schedule()
    {
        return $this->belongsTo(QaSchedule::class, 'qa_schedule_id');
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

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function evidences()
    {
        return $this->hasMany(RetestEvidence::class);
    }
}
