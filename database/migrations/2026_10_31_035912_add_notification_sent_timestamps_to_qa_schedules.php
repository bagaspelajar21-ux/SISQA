<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('qa_schedules', function (Blueprint $table) {
            $table->timestamp('start_notification_sent_at')->nullable();
            $table->timestamp('end_notification_sent_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qa_schedules', function (Blueprint $table) {
            $table->dropColumn(['start_notification_sent_at', 'end_notification_sent_at']);
        });
    }
};
