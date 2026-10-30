<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'flow_code',
        'title',
        'application_id',
        'module_id',
        'menu_id',
        'description',
        'process_goal',
        'actors',
        'prerequisites',
        'expected_outcome',
        'version',
        'status',
        'created_by',
        'updated_by',
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

    public function steps()
    {
        return $this->hasMany(ApplicationFlowStep::class, 'flow_id')->orderBy('step_number');
    }

    public function files()
    {
        return $this->hasMany(ApplicationFlowFile::class, 'flow_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'application_flow_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'application_flow_id');
    }
}
