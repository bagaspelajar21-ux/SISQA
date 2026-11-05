<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationMenu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'module_id',
        'name',
        'code',
        'url_or_route',
        'description',
        'order_index',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_index' => 'integer',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function module()
    {
        return $this->belongsTo(ApplicationModule::class, 'module_id');
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'menu_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'menu_id');
    }

    public function schedules()
    {
        return $this->hasMany(QaSchedule::class, 'menu_id');
    }

    public function qaSchedules()
    {
        return $this->belongsToMany(QaSchedule::class, 'qa_schedule_menus', 'menu_id', 'qa_schedule_id');
    }
}
