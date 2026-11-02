<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ApplicationModule;
use App\Models\AutomatedReminderSetting;
use App\Models\Bug;
use App\Models\BugEvidence;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase as TestCaseModel;
use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class QaScheduleLockTest extends TestCase
{
    use RefreshDatabase;

    private string $permissionsConfigPath;

    private ?string $permissionsConfigBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionsConfigPath = storage_path('app/role_user_permissions.json');
        $this->permissionsConfigBackup = file_exists($this->permissionsConfigPath)
            ? file_get_contents($this->permissionsConfigPath)
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->permissionsConfigBackup === null) {
            if (file_exists($this->permissionsConfigPath)) {
                unlink($this->permissionsConfigPath);
            }
        } else {
            file_put_contents($this->permissionsConfigPath, $this->permissionsConfigBackup);
        }

        parent::tearDown();
    }

    public function test_returns_403_when_user_executes_test_case_after_its_qa_schedule_expires(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], ['execute_test']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $schedule->testCases()->attach($testCase->id);
        Sanctum::actingAs($user);

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertForbidden()
            ->assertJsonPath('message', "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$schedule->end_time->format('d/m/Y H:i')}. Masa toleransi 0 menit telah habis; hubungi Admin jika perlu melanjutkan.");

        $this->assertDatabaseCount('test_executions', 0);
    }

    public function test_user_can_execute_during_grace_period_but_is_locked_after_it_expires(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], ['execute_test']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $schedule->testCases()->attach($testCase->id);
        AutomatedReminderSetting::current()->update([
            'qa_lock_mode' => 'grace_then_lock',
            'qa_lock_minutes' => 5,
        ]);
        Sanctum::actingAs($user);
        $this->travelTo($schedule->end_time->copy()->addMinutes(4));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertCreated();

        $this->travelTo($schedule->end_time->copy()->addMinutes(6));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertForbidden()
            ->assertJsonPath('message', "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$schedule->end_time->format('d/m/Y H:i')}. Masa toleransi 5 menit telah habis; hubungi Admin jika perlu melanjutkan.");

        $this->assertDatabaseCount('test_executions', 1);
    }

    public function test_temporary_lock_ends_after_the_admin_configured_minutes(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], ['execute_test']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $schedule->testCases()->attach($testCase->id);
        AutomatedReminderSetting::current()->update([
            'qa_lock_mode' => 'lock_for_duration',
            'qa_lock_minutes' => 10,
        ]);
        Sanctum::actingAs($user);
        $this->travelTo($schedule->end_time->copy()->addMinutes(5));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertForbidden()
            ->assertJsonPath('message', "Modul/menu terkunci karena jadwal QA ({$schedule->schedule_code}) telah berakhir pada {$schedule->end_time->format('d/m/Y H:i')}. Penguncian sementara berlangsung selama 10 menit setelah jadwal berakhir.");

        $this->travelTo($schedule->end_time->copy()->addMinutes(10));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertCreated();
    }

    public function test_temporary_lock_hides_qa_records_then_releases_all_three_lists(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions(
            $user->id,
            ['/test-cases', '/bugs', '/retests'],
            [],
        );
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $module = $this->assignScheduleToModule($testCase, $schedule);
        $bug = $this->createBug($user, $testCase, $schedule);
        $retest = Retest::create([
            'bug_id' => $bug->id,
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $schedule->id,
            'application_id' => $schedule->application_id,
            'module_id' => $module->id,
            'tester_id' => $user->id,
            'retest_date' => now(),
            'actual_result' => 'Belum berhasil.',
            'status' => 'Failed',
        ]);
        AutomatedReminderSetting::current()->update([
            'qa_lock_mode' => 'lock_for_duration',
            'qa_lock_minutes' => 10,
        ]);
        Sanctum::actingAs($user);
        $this->travelTo($schedule->end_time->copy()->addMinutes(5));

        $this->getJson('/api/test-cases')
            ->assertOk()
            ->assertJsonMissing(['id' => $testCase->id])
            ->assertJsonPath('qa_lock_policy.minutes', 10);
        $this->getJson('/api/bugs')
            ->assertOk()
            ->assertJsonMissing(['id' => $bug->id]);
        $this->getJson('/api/retests')
            ->assertOk()
            ->assertJsonMissing(['id' => $retest->id]);

        $this->travelTo($schedule->end_time->copy()->addMinutes(10));

        $this->getJson('/api/test-cases')
            ->assertOk()
            ->assertJsonFragment(['id' => $testCase->id]);
        $this->getJson('/api/bugs')
            ->assertOk()
            ->assertJsonFragment(['id' => $bug->id]);
        $this->getJson('/api/retests')
            ->assertOk()
            ->assertJsonFragment(['id' => $retest->id]);
    }

    public function test_admin_can_execute_test_case_after_its_qa_schedule_expires(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($admin);
        $schedule->testCases()->attach($testCase->id);
        Sanctum::actingAs($admin);

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertCreated();

        $this->assertDatabaseHas('test_executions', [
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $schedule->id,
            'status' => 'PASS',
        ]);
    }

    public function test_user_cannot_list_or_open_test_cases_in_an_expired_module(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], []);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $this->assignScheduleToModule($testCase, $schedule);
        Sanctum::actingAs($user);

        $this->getJson('/api/test-cases')
            ->assertOk()
            ->assertJsonMissing(['id' => $testCase->id]);
        $this->getJson("/api/test-cases/{$testCase->id}")
            ->assertForbidden();
    }

    public function test_admin_can_list_and_open_test_cases_in_an_expired_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($admin);
        $this->assignScheduleToModule($testCase, $schedule);
        Sanctum::actingAs($admin);

        $this->getJson('/api/test-cases')
            ->assertOk()
            ->assertJsonFragment(['id' => $testCase->id]);
        $this->getJson("/api/test-cases/{$testCase->id}")
            ->assertOk()
            ->assertJsonPath('test_case.id', $testCase->id);
    }

    public function test_user_cannot_list_or_open_bugs_and_retests_in_an_expired_module(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/bugs', '/retests'], []);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $this->assignScheduleToModule($testCase, $schedule);
        $bug = $this->createBug($user, $testCase, $schedule);
        $retest = Retest::create([
            'bug_id' => $bug->id,
            'test_case_id' => $testCase->id,
            'application_id' => $schedule->application_id,
            'tester_id' => $user->id,
            'retest_date' => now(),
            'actual_result' => 'Belum berhasil.',
            'status' => 'Failed',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/bugs')
            ->assertOk()
            ->assertJsonMissing(['id' => $bug->id]);
        $this->getJson("/api/bugs/{$bug->id}")
            ->assertForbidden();
        $evidence = BugEvidence::create([
            'bug_id' => $bug->id,
            'evidence_type' => 'initial',
            'file_name' => 'evidence.png',
            'original_name' => 'evidence.png',
            'file_path' => 'evidences/bugs/evidence.png',
            'mime_type' => 'image/png',
            'file_size' => 100,
            'uploaded_by' => $user->id,
        ]);
        $this->getJson("/api/files/bug/{$evidence->id}/download")
            ->assertForbidden();
        $this->getJson('/api/retests')
            ->assertOk()
            ->assertJsonMissing(['id' => $retest->id]);
        $this->getJson("/api/retests/{$retest->id}")
            ->assertForbidden();
    }

    public function test_excel_import_cannot_create_records_in_an_expired_module(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions(
            $user->id,
            ['/test-cases', '/bugs'],
            ['import_test_excel', 'import_bug_excel'],
        );
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $this->assignScheduleToModule($testCase, $schedule);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Aplikasi', 'Modul QA', '', 'Skenario baru', 'Deskripsi bug', '', '', '', 'Hasil yang diharapkan', '', ''],
            ['Aplikasi QA', 'Modul QA', '', 'Skenario baru', 'Deskripsi bug', '', '', '', 'Hasil yang diharapkan', '', ''],
        ]);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'qa-lock-import-');
        (new Xlsx($spreadsheet))->save($temporaryPath);
        $spreadsheet->disconnectWorksheets();
        Sanctum::actingAs($user);

        try {
            $this->post('/api/test-cases/import-excel', [
                'file' => new UploadedFile($temporaryPath, 'test-cases.xlsx', null, null, true),
            ])->assertForbidden();
            $this->post('/api/bugs/import-excel', [
                'file' => new UploadedFile($temporaryPath, 'bugs.xlsx', null, null, true),
            ])->assertForbidden();
        } finally {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $this->assertDatabaseCount('test_cases', 1);
        $this->assertDatabaseCount('bugs', 0);
    }

    public function test_active_module_schedule_keeps_test_cases_accessible(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], []);
        [$testCase, $expiredSchedule] = $this->createExpiredQaSchedule($user);
        $module = $this->assignScheduleToModule($testCase, $expiredSchedule);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-ACTIVE',
            'title' => 'Jadwal QA aktif',
            'application_id' => $expiredSchedule->application_id,
            'module_id' => $module->id,
            'tester_id' => $user->id,
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/test-cases/{$testCase->id}")
            ->assertOk()
            ->assertJsonPath('test_case.id', $testCase->id);
    }

    public function test_follow_schedule_lock_opens_when_the_next_schedule_for_the_same_module_starts(): void
    {
        $this->travelTo('2026-03-10 09:00:00');
        $user = User::factory()->create(['role' => 'admin']);
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-FOLLOW',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul Terjadwal',
            'code' => 'MOD-QA-FOLLOW',
        ]);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-PAST',
            'title' => 'Jadwal yang sudah selesai',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'tester_id' => $user->id,
            'start_time' => '2026-03-10 07:00:00',
            'end_time' => '2026-03-10 08:59:00',
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-NEXT',
            'title' => 'Jadwal QA berikutnya',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'tester_id' => $user->id,
            'start_time' => '2026-03-10 10:00:00',
            'end_time' => '2026-03-10 11:00:00',
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'Scheduled',
        ]);
        AutomatedReminderSetting::current()->update(['qa_lock_mode' => 'follow_schedule']);
        Sanctum::actingAs($user);

        $this->getJson("/api/qa-access/status?module_id={$module->id}")
            ->assertOk()
            ->assertJsonPath('status.locked', true)
            ->assertJsonPath('status.schedule_code', 'SCH-QA-PAST')
            ->assertJsonPath('status.unlock_at', '2026-03-10T10:00:00+00:00')
            ->assertJsonPath('status.waiting_for_schedule', false);

        $this->travelTo('2026-03-10 10:00:00');

        $this->getJson("/api/qa-access/status?module_id={$module->id}")
            ->assertOk()
            ->assertJsonPath('status.locked', false);
    }

    public function test_follow_schedule_lock_does_not_open_for_a_different_module_schedule(): void
    {
        $this->travelTo('2026-03-10 09:00:00');
        $user = User::factory()->create(['role' => 'admin']);
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-OTHER-FOLLOW',
        ]);
        $lockedModule = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul Terkunci',
            'code' => 'MOD-QA-LOCKED-FOLLOW',
        ]);
        $otherModule = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul Lain',
            'code' => 'MOD-QA-OTHER-FOLLOW',
        ]);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-LOCKED',
            'title' => 'Jadwal yang berakhir',
            'application_id' => $application->id,
            'module_id' => $lockedModule->id,
            'tester_id' => $user->id,
            'start_time' => '2026-03-10 07:00:00',
            'end_time' => '2026-03-10 08:59:00',
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-OTHER',
            'title' => 'Jadwal pada modul lain',
            'application_id' => $application->id,
            'module_id' => $otherModule->id,
            'tester_id' => $user->id,
            'start_time' => '2026-03-10 10:00:00',
            'end_time' => '2026-03-10 11:00:00',
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'Scheduled',
        ]);
        AutomatedReminderSetting::current()->update(['qa_lock_mode' => 'follow_schedule']);
        Sanctum::actingAs($user);

        $this->getJson("/api/qa-access/status?module_id={$lockedModule->id}")
            ->assertOk()
            ->assertJsonPath('status.locked', true)
            ->assertJsonPath('status.unlock_at', null)
            ->assertJsonPath('status.waiting_for_schedule', true);
    }

    public function test_temporary_lock_status_returns_the_automatic_unlock_time(): void
    {
        $this->travelTo('2026-03-10 09:05:00');
        $user = User::factory()->create(['role' => 'admin']);
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-TEMPORARY',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul QA',
            'code' => 'MOD-QA-TEMPORARY',
        ]);
        QaSchedule::create([
            'schedule_code' => 'SCH-QA-TEMPORARY',
            'title' => 'Jadwal QA yang berakhir',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'tester_id' => $user->id,
            'start_time' => '2026-03-10 07:00:00',
            'end_time' => '2026-03-10 09:00:00',
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        AutomatedReminderSetting::current()->update([
            'qa_lock_mode' => 'lock_for_duration',
            'qa_lock_minutes' => 10,
        ]);
        Sanctum::actingAs($user);

        $this->getJson("/api/qa-access/status?module_id={$module->id}")
            ->assertOk()
            ->assertJsonPath('status.locked', true)
            ->assertJsonPath('status.unlock_at', '2026-03-10T09:10:00+00:00')
            ->assertJsonPath('status.waiting_for_schedule', false);
    }

    public function test_returns_403_when_user_updates_bug_after_linked_schedule_expires(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/bugs'], ['edit_bug']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $bug = $this->createBug($user, $testCase, $schedule);
        Sanctum::actingAs($user);

        $this->putJson("/api/bugs/{$bug->id}", [
            'title' => $bug->title,
            'application_id' => $bug->application_id,
            'test_case_id' => $bug->test_case_id,
            'qa_schedule_id' => $bug->qa_schedule_id,
            'description' => $bug->description,
            'severity' => $bug->severity,
            'priority' => $bug->priority,
            'status' => 'Closed',
        ])->assertForbidden();

        $this->assertDatabaseHas('bugs', [
            'id' => $bug->id,
            'status' => 'Open',
        ]);
    }

    public function test_returns_403_when_user_deletes_bug_after_linked_schedule_expires(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/bugs'], ['edit_bug']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $bug = $this->createBug($user, $testCase, $schedule);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/bugs/{$bug->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('bugs', ['id' => $bug->id, 'deleted_at' => null]);
    }

    public function test_returns_403_when_user_creates_bug_for_an_expired_test_case_schedule(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/bugs'], ['create_bug']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $schedule->testCases()->attach($testCase->id);
        $unlockedModule = ApplicationModule::create([
            'application_id' => $schedule->application_id,
            'name' => 'Modul lain',
            'code' => 'MOD-QA-UNLOCKED',
        ]);
        $activeSchedule = QaSchedule::create([
            'schedule_code' => 'SCH-QA-OTHER',
            'title' => 'Jadwal modul lain',
            'application_id' => $schedule->application_id,
            'module_id' => $unlockedModule->id,
            'tester_id' => $user->id,
            'start_time' => now()->subMinute(),
            'end_time' => now()->addHour(),
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/bugs', [
            'title' => 'Bug QA yang terlambat',
            'application_id' => $schedule->application_id,
            'module_id' => $unlockedModule->id,
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $activeSchedule->id,
            'description' => 'Bug tidak boleh dibuat setelah jadwal berakhir.',
            'severity' => 'Minor',
            'priority' => 'Medium',
        ])->assertForbidden();

        $this->assertDatabaseCount('bugs', 0);
    }

    public function test_returns_403_when_user_retests_bug_after_linked_schedule_expires(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/retests'], ['create_retest']);
        [$testCase, $schedule] = $this->createExpiredQaSchedule($user);
        $bug = $this->createBug($user, $testCase, $schedule);
        Sanctum::actingAs($user);

        $this->postJson('/api/retests', [
            'bug_id' => $bug->id,
            'actual_result' => 'Belum berhasil.',
            'status' => 'Failed',
        ])->assertForbidden();

        $this->assertDatabaseCount('retests', 0);
        $this->assertDatabaseHas('bugs', ['id' => $bug->id, 'status' => 'Open']);
    }

    public function test_returns_403_when_user_changes_or_deletes_an_expired_schedule(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/schedules'], ['create_schedule']);
        [, $schedule] = $this->createExpiredQaSchedule($user);
        Sanctum::actingAs($user);

        $this->putJson("/api/schedules/{$schedule->id}", [])
            ->assertForbidden();
        $this->deleteJson("/api/schedules/{$schedule->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('qa_schedules', [
            'id' => $schedule->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * @return array{TestCaseModel, QaSchedule}
     */
    private function createExpiredQaSchedule(User $tester): array
    {
        $this->freezeTime();
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-LOCK',
        ]);
        $testCase = TestCaseModel::create([
            'test_case_code' => 'TC-QA-LOCK',
            'title' => 'Skenario QA',
            'application_id' => $application->id,
            'expected_result' => 'Berhasil diproses',
        ]);
        $schedule = QaSchedule::create([
            'schedule_code' => 'SCH-QA-LOCK',
            'title' => 'Jadwal QA',
            'application_id' => $application->id,
            'tester_id' => $tester->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinute(),
            'target_test_cases_count' => 1,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);

        return [$testCase, $schedule];
    }

    private function createBug(User $reporter, TestCaseModel $testCase, QaSchedule $schedule): Bug
    {
        return Bug::create([
            'bug_code' => 'BUG-QA-LOCK',
            'title' => 'Bug pada QA',
            'application_id' => $schedule->application_id,
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $schedule->id,
            'description' => 'Deskripsi bug',
            'reporter_id' => $reporter->id,
            'report_date' => now()->toDateString(),
            'severity' => 'Minor',
            'priority' => 'Medium',
            'status' => 'Open',
        ]);
    }

    private function assignScheduleToModule(TestCaseModel $testCase, QaSchedule $schedule): ApplicationModule
    {
        $module = ApplicationModule::create([
            'application_id' => $schedule->application_id,
            'name' => 'Modul QA',
            'code' => 'MOD-QA-LOCK',
        ]);
        $testCase->update(['module_id' => $module->id]);
        $schedule->update(['module_id' => $module->id]);

        return $module;
    }
}
