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
        Schema::create('admin_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->text('message');
            $table->enum('recipient_type', ['all', 'role', 'user']);
            $table->string('recipient_role')->nullable();
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['delivered_at', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_reminders');
    }
};
