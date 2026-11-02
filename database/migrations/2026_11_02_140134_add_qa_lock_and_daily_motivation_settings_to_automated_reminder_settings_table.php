<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('automated_reminder_settings', function (Blueprint $table) {
            $table->string('qa_lock_mode', 30)->default('grace_then_lock');
            $table->unsignedSmallInteger('qa_lock_minutes')->default(0);
            $table->boolean('daily_motivation_enabled')->default(true);
            $table->json('daily_motivations')->nullable();
        });

        DB::table('automated_reminder_settings')->where('id', 1)->update([
            'daily_motivations' => json_encode([
                'Setiap bug yang ditemukan adalah kesempatan memperkuat kualitas produk.',
                'Pengujian yang teliti hari ini mencegah masalah besar esok hari.',
                'Langkah kecil yang konsisten membawa rilis lebih dekat pada kualitas terbaik.',
                'Temukan, catat, dan bantu tim memperbaiki—itulah dampak QA.',
                'Kualitas perangkat lunak dibangun dari rasa ingin tahu dan ketelitian.',
                'Jangan takut menemukan bug; setiap temuan adalah kemajuan.',
                'Kerja tim yang baik membuat setiap proses QA lebih bermakna.',
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automated_reminder_settings', function (Blueprint $table) {
            $table->dropColumn([
                'qa_lock_mode',
                'qa_lock_minutes',
                'daily_motivation_enabled',
                'daily_motivations',
            ]);
        });
    }
};
