<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('bug_code')->unique();
            $table->string('title');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('application_modules')->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('application_menus')->nullOnDelete();
            $table->foreignId('test_case_id')->nullable()->constrained('test_cases')->nullOnDelete();
            $table->foreignId('qa_schedule_id')->nullable()->constrained('qa_schedules')->nullOnDelete();
            $table->foreignId('application_flow_id')->nullable()->constrained('application_flows')->nullOnDelete();
            $table->foreignId('application_flow_step_id')->nullable()->constrained('application_flow_steps')->nullOnDelete();
            $table->text('description');
            $table->text('steps_to_reproduce')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('report_date');
            $table->enum('severity', ['Critical', 'Major', 'Minor', 'Trivial'])->default('Minor');
            $table->enum('priority', ['Urgent', 'High', 'Medium', 'Low'])->default('Medium');
            $table->enum('status', ['Open', 'Assigned', 'In Progress', 'Fixed', 'Ready for Retest', 'Reopened', 'Closed', 'Rejected'])->default('Open');
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('duplicate_of_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('duplicate_of_id')->references('id')->on('bugs')->nullOnDelete();
        });

        Schema::create('bug_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('field_changed')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bug_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->enum('evidence_type', ['initial', 'fix', 'retest'])->default('initial');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('retests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->foreignId('test_case_id')->nullable()->constrained('test_cases')->nullOnDelete();
            $table->foreignId('qa_schedule_id')->nullable()->constrained('qa_schedules')->nullOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('module_id')->nullable()->constrained('application_modules')->nullOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('application_menus')->nullOnDelete();
            $table->foreignId('tester_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('retest_date');
            $table->string('tested_version')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->enum('status', ['Pending Retest', 'Passed', 'Failed', 'Reopened'])->default('Pending Retest');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('retest_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retest_id')->constrained('retests')->cascadeOnDelete();
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
        Schema::dropIfExists('retest_evidences');
        Schema::dropIfExists('retests');
        Schema::dropIfExists('bug_evidences');
        Schema::dropIfExists('bug_histories');
        Schema::dropIfExists('bugs');
    }
};
