<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugEvidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'bug_id',
        'evidence_type', // initial, fix, retest
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
