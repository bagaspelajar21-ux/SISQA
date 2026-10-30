<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageGuideStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'usage_guide_id',
        'step_number',
        'title',
        'description',
        'image_path',
        'notes',
    ];

    protected $casts = [
        'step_number' => 'integer',
    ];

    public function guide()
    {
        return $this->belongsTo(UsageGuide::class, 'usage_guide_id');
    }
}
