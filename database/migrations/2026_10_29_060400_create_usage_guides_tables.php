<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_guides', function (Blueprint $table) {
            $table->id();
            $table->string('guide_code')->unique();
            $table->string('title');
            $table->string('menu_name');
            $table->string('category')->default('Umum');
            $table->text('menu_purpose')->nullable();
            $table->text('menu_function')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('tips')->nullable();
            $table->text('troubleshooting')->nullable();
            $table->string('version')->default('1.0');
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Draft');
            $table->enum('observation_status', ['Belum Diamati', 'Sudah Diamati'])->default('Belum Diamati');
            $table->foreignId('last_observed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('last_observed_at')->nullable();
            $table->string('last_observed_version')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('usage_guide_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_guide_id')->constrained('usage_guides')->cascadeOnDelete();
            $table->integer('step_number');
            $table->string('title');
            $table->text('description');
            $table->string('image_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_guide_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_guide_id')->constrained('usage_guides')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('usage_guide_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_guide_id')->constrained('usage_guides')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('observed_version');
            $table->dateTime('observed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_guide_observations');
        Schema::dropIfExists('usage_guide_files');
        Schema::dropIfExists('usage_guide_steps');
        Schema::dropIfExists('usage_guides');
    }
};
