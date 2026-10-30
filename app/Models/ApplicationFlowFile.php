<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationFlowFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    public function flow()
    {
        return $this->belongsTo(ApplicationFlow::class, 'flow_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
