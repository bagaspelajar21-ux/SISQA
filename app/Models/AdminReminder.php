<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminReminder extends Model
{
    protected $fillable = [
        'title',
        'message',
        'recipient_type',
        'recipient_role',
        'recipient_user_id',
        'scheduled_at',
        'delivered_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}
