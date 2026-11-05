<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Petakan menu_name panduan existing ke route path frontend agar status
     * akses role dapat ditampilkan sebagai ceklis.
     */
    private const MENU_NAME_TO_PATH = [
        'Dashboard QA' => '/',
        'Jadwal QA' => '/schedules',
        'Test Management' => '/test-cases',
        'Bug Tracking' => '/bugs',
        'Retesting' => '/retests',
        'Flow Aplikasi' => '/flows',
        'Panduan SISQA' => '/guides',
        'QA Report' => '/reports',
        'Report WA' => '/reports/wa',
        'Report Mingguan' => '/reports/weekly',
        'Activity Logs' => '/activity-logs',
        'Master Aplikasi' => '/master/apps',
        'Master Modul' => '/master/modules',
        'Master Menu' => '/master/menus',
        'Manajemen Pengguna' => '/users',
        'Profil Saya' => '/profile',
        'Analisis Pekerjaan' => '/admin/work-analysis',
        'Pengingat & Notifikasi' => '/admin/reminders',
        'Pengaturan QA & Motivasi' => '/admin/qa-settings',
    ];

    public function up(): void
    {
        Schema::table('usage_guides', function (Blueprint $table) {
            $table->string('menu_path')->nullable()->after('menu_name')->index();
        });

        foreach (self::MENU_NAME_TO_PATH as $menuName => $path) {
            DB::table('usage_guides')
                ->where('menu_name', $menuName)
                ->update(['menu_path' => $path]);
        }
    }

    public function down(): void
    {
        Schema::table('usage_guides', function (Blueprint $table) {
            $table->dropIndex(['menu_path']);
            $table->dropColumn('menu_path');
        });
    }
};
