<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'version',
        'environment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function modules()
    {
        return $this->hasMany(ApplicationModule::class)->orderBy('order_index');
    }

    public function menus()
    {
        return $this->hasMany(ApplicationMenu::class)->orderBy('order_index');
    }

    public function flows()
    {
        return $this->hasMany(ApplicationFlow::class);
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class);
    }

    public function schedules()
    {
        return $this->hasMany(QaSchedule::class);
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class);
    }
}
