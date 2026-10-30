<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageGuideFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'usage_guide_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    public function guide()
    {
        return $this->belongsTo(UsageGuide::class, 'usage_guide_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
