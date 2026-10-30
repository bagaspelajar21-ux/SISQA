<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BugHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'bug_id',
        'user_id',
        'field_changed',
        'old_value',
        'new_value',
        'notes',
    ];

    public function bug()
    {
        return $this->belongsTo(Bug::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
