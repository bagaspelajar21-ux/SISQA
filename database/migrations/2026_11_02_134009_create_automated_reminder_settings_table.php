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
        Schema::create('automated_reminder_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->boolean('qa_enabled')->default(true);
            $table->time('qa_time')->default('08:00:00');
            $table->boolean('daily_report_enabled')->default(true);
            $table->time('daily_report_time')->default('16:00:00');
            $table->boolean('weekly_report_enabled')->default(true);
            $table->time('weekly_report_time')->default('16:00:00');
            $table->boolean('monthly_report_enabled')->default(false);
            $table->time('monthly_report_time')->default('16:00:00');
            $table->boolean('motivation_enabled')->default(true);
            $table->string('motivation_message', 500)->default('Semangat QA! Setiap bug yang ditemukan adalah langkah menuju kualitas yang lebih baik.');
            $table->timestamps();
        });

        DB::table('automated_reminder_settings')->insert([
            'id' => 1,
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
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automated_reminder_settings');
    }
};
