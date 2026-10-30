<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestExecutionEvidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'test_execution_id',
        'file_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    public function execution()
    {
        return $this->belongsTo(TestExecution::class, 'test_execution_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
