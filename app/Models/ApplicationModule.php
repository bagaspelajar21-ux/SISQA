<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationModule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'name',
        'code',
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

    public function menus()
    {
        return $this->hasMany(ApplicationMenu::class, 'module_id')->orderBy('order_index');
    }

    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'module_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'module_id');
    }
}
