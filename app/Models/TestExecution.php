<?php

namespace App\Models;

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
