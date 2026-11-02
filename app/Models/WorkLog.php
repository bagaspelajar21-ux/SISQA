<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkLog extends Model
{
    protected $table = 'personal_work_logs';

    protected $fillable = [
        'work_date',
        'description',
    ];

    protected $hidden = [
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
