<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qa_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_code')->unique();
            $table->string('title');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('application_modules')->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('application_menus')->nullOnDelete();
            $table->foreignId('application_flow_id')->nullable()->constrained('application_flows')->nullOnDelete();
            $table->enum('testing_type', ['Daily', 'Weekly', 'Regression', 'Retesting', 'Release', 'Smoke Testing'])->default('Daily');
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('target_test_cases_count')->default(0);
            $table->enum('priority', ['Urgent', 'High', 'Medium', 'Low'])->default('Medium');
            $table->text('notes')->nullable();
            $table->enum('status', ['Scheduled', 'In Progress', 'Completed', 'Overdue', 'Cancelled'])->default('Scheduled');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->string('test_case_code')->unique();
            $table->string('title');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('application_modules')->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('application_menus')->nullOnDelete();
            $table->foreignId('application_flow_id')->nullable()->constrained('application_flows')->nullOnDelete();
            $table->foreignId('application_flow_step_id')->nullable()->constrained('application_flow_steps')->nullOnDelete();
            $table->text('description')->nullable();
            $table->text('preconditions')->nullable();
            $table->text('test_steps')->nullable();
            $table->text('test_data')->nullable();
            $table->text('expected_result');
            $table->foreignId('assigned_tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', ['Urgent', 'High', 'Medium', 'Low'])->default('Medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('qa_schedule_test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qa_schedule_id')->constrained('qa_schedules')->cascadeOnDelete();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['qa_schedule_id', 'test_case_id']);
        });

        Schema::create('test_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained('test_cases')->cascadeOnDelete();
            $table->foreignId('qa_schedule_id')->nullable()->constrained('qa_schedules')->nullOnDelete();
            $table->foreignId('tester_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['PASS', 'FAILED', 'PENDING', 'NOT TESTED'])->default('NOT TESTED');
            $table->text('actual_result')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('executed_at');
            $table->timestamps();
        });

        Schema::create('test_execution_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_execution_id')->constrained('test_executions')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_execution_evidences');
        Schema::dropIfExists('test_executions');
        Schema::dropIfExists('qa_schedule_test_cases');
        Schema::dropIfExists('test_cases');
        Schema::dropIfExists('qa_schedules');
    }
};
