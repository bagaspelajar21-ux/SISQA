<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'profile_photo_path',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? '/storage/'.ltrim($this->profile_photo_path, '/')
            : null;
    }

    public function schedules()
    {
        return $this->hasMany(QaSchedule::class, 'tester_id');
    }

    public function testExecutions()
    {
        return $this->hasMany(TestExecution::class, 'tester_id');
    }

    public function reportedBugs()
    {
        return $this->hasMany(Bug::class, 'reporter_id');
    }

    public function assignedBugs()
    {
        return $this->hasMany(Bug::class, 'assignee_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id');
    }

    public function workLogs(): HasMany
    {
        return $this->hasMany(WorkLog::class);
    }

    public function createdAdminReminders(): HasMany
    {
        return $this->hasMany(AdminReminder::class, 'created_by');
    }
}
