<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationFlowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'step_number',
        'title',
        'step_type',
        'activity_description',
        'actor',
        'input_data',
        'expected_result',
        'next_step_id',
        'decision_branches',
        'notes',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'decision_branches' => 'array',
    ];

    public function flow()
    {
        return $this->belongsTo(ApplicationFlow::class, 'flow_id');
    }

    public function nextStep()
    {
        return $this->belongsTo(ApplicationFlowStep::class, 'next_step_id');
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'application_flow_step_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'application_flow_step_id');
    }
}
