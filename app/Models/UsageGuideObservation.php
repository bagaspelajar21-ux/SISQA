<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageGuideObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'usage_guide_id',
        'admin_id',
        'observed_version',
        'observed_at',
        'notes',
    ];

    protected $casts = [
        'observed_at' => 'datetime',
    ];

    public function guide()
    {
        return $this->belongsTo(UsageGuide::class, 'usage_guide_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
