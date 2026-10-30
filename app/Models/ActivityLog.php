<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'result',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to log activity safely without exposing passwords or tokens.
     */
    public static function record(
        string $action,
        string $module,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $result = 'Success',
        ?int $userId = null
    ): self {
        $userId = $userId ?: Auth::id();

        // Sanitize sensitive fields
        $sanitize = function (?array $data) {
            if (!$data) return null;
            $sensitiveKeys = ['password', 'password_confirmation', 'token', 'remember_token', 'access_token', 'secret'];
            foreach ($sensitiveKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $data[$key] = '********';
                }
            }
            return $data;
        };

        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_values' => $sanitize($oldValues),
            'new_values' => $sanitize($newValues),
            'result' => $result,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
