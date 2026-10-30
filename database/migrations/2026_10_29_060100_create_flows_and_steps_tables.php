<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_flows', function (Blueprint $table) {
            $table->id();
            $table->string('flow_code')->unique();
            $table->string('title');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('application_modules')->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('application_menus')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('process_goal')->nullable();
            $table->text('actors')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('expected_outcome')->nullable();
            $table->string('version')->default('1.0');
            $table->enum('status', ['Draft', 'Published', 'Archived'])->default('Draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('application_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('application_flows')->cascadeOnDelete();
            $table->integer('step_number');
            $table->string('title');
            $table->enum('step_type', ['Start', 'Process', 'Decision', 'End'])->default('Process');
            $table->text('activity_description')->nullable();
            $table->string('actor')->nullable();
            $table->text('input_data')->nullable();
            $table->text('expected_result')->nullable();
            $table->unsignedBigInteger('next_step_id')->nullable();
            $table->json('decision_branches')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('application_flow_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('application_flows')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_flow_files');
        Schema::dropIfExists('application_flow_steps');
        Schema::dropIfExists('application_flows');
    }
};
