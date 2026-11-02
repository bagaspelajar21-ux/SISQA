<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomatedReminderSetting extends Model
{
    protected $fillable = [
        'qa_enabled',
        'qa_time',
        'daily_report_enabled',
        'daily_report_time',
        'weekly_report_enabled',
        'weekly_report_time',
        'monthly_report_enabled',
        'monthly_report_time',
        'motivation_enabled',
        'motivation_message',
        'qa_lock_mode',
        'qa_lock_minutes',
        'daily_motivation_enabled',
        'daily_motivations',
        'daily_motivation_image_path',
    ];

    protected function casts(): array
    {
        return [
            'qa_enabled' => 'boolean',
            'daily_report_enabled' => 'boolean',
            'weekly_report_enabled' => 'boolean',
            'monthly_report_enabled' => 'boolean',
            'motivation_enabled' => 'boolean',
            'qa_lock_minutes' => 'integer',
            'daily_motivation_enabled' => 'boolean',
            'daily_motivations' => 'array',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], [
            'qa_enabled' => true,
            'qa_time' => '08:00:00',
            'daily_report_enabled' => true,
            'daily_report_time' => '16:00:00',
            'weekly_report_enabled' => true,
            'weekly_report_time' => '16:00:00',
            'monthly_report_enabled' => false,
            'monthly_report_time' => '16:00:00',
            'motivation_enabled' => true,
            'motivation_message' => 'Semangat QA! Setiap bug yang ditemukan adalah langkah menuju kualitas yang lebih baik.',
            'qa_lock_mode' => 'grace_then_lock',
            'qa_lock_minutes' => 0,
            'daily_motivation_enabled' => true,
            'daily_motivations' => [
                'Setiap bug yang ditemukan adalah kesempatan memperkuat kualitas produk.',
                'Pengujian yang teliti hari ini mencegah masalah besar esok hari.',
                'Langkah kecil yang konsisten membawa rilis lebih dekat pada kualitas terbaik.',
                'Temukan, catat, dan bantu tim memperbaiki—itulah dampak QA.',
                'Kualitas perangkat lunak dibangun dari rasa ingin tahu dan ketelitian.',
                'Jangan takut menemukan bug; setiap temuan adalah kemajuan.',
                'Kerja tim yang baik membuat setiap proses QA lebih bermakna.',
            ],
        ]);
    }
}
