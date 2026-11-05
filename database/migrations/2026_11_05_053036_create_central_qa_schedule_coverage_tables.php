<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_schedule_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_schedule_id')->constrained('qa_schedules')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('application_menus')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['qa_schedule_id', 'menu_id']);
            $table->index('menu_id');
        });

        Schema::create('qa_schedule_testers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_schedule_id')->constrained('qa_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['qa_schedule_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('qa_schedules', function (Blueprint $table) {
            $table->enum('menu_scope', ['all', 'selected'])->default('selected')->after('menu_id');
        });
    }

    public function down(): void
    {
        Schema::table('qa_schedules', function (Blueprint $table) {
            $table->dropColumn('menu_scope');
        });

        Schema::dropIfExists('qa_schedule_testers');
        Schema::dropIfExists('qa_schedule_menus');
    }
};
