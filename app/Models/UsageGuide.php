<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsageGuide extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guide_code',
        'title',
        'menu_name',
        'category',
        'menu_purpose',
        'menu_function',
        'prerequisites',
        'tips',
        'troubleshooting',
        'version',
        'status', // Draft, Published, Archived
        'observation_status', // Belum Diamati, Sudah Diamati
        'last_observed_by',
        'last_observed_at',
        'last_observed_version',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_observed_at' => 'datetime',
    ];

    public function steps()
    {
        return $this->hasMany(UsageGuideStep::class)->orderBy('step_number');
    }

    public function files()
    {
        return $this->hasMany(UsageGuideFile::class);
    }

    public function observations()
    {
        return $this->hasMany(UsageGuideObservation::class)->orderBy('observed_at', 'desc');
    }

    public function lastObserver()
    {
        return $this->belongsTo(User::class, 'last_observed_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
