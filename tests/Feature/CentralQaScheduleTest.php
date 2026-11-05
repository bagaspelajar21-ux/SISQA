<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\AutomatedReminderSetting;
use App\Models\QaSchedule;
use App\Models\TestCase as TestCaseModel;
use App\Models\TestExecution;
use App\Models\User;
use App\Services\QaScheduleLockService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CentralQaScheduleTest extends TestCase
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

    public function test_admin_creates_central_schedule_with_all_module_menus(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/schedules', [
            'title' => 'QA Master Data November',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_scope' => 'all',
            'tester_ids' => [$admin->id],
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(5)->toDateTimeString(),
            'priority' => 'High',
            'testing_type' => 'Regression',
            'status' => 'Scheduled',
        ]);

        $response->assertCreated();
        $schedule = QaSchedule::latest('id')->first();

        $this->assertSame('all', $schedule->menu_scope);
        $this->assertEqualsCanonicalizing(
            $menus->pluck('id')->all(),
            $schedule->menus->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            $testCases->pluck('id')->all(),
            $schedule->testCases->pluck('id')->all()
        );
        $this->assertTrue($schedule->isAssignedTo($admin->id));
    }

    public function test_admin_creates_central_schedule_with_selected_menus_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        Sanctum::actingAs($admin);

        $this->postJson('/api/schedules', [
            'title' => 'QA Master Data - Sebagian',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_scope' => 'selected',
            'menu_ids' => [$menus[0]->id, $menus[1]->id],
            'tester_ids' => [$admin->id],
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(5)->toDateTimeString(),
            'priority' => 'Medium',
            'testing_type' => 'Daily',
        ])->assertCreated();

        $schedule = QaSchedule::latest('id')->first();
        $this->assertEqualsCanonicalizing([$menus[0]->id, $menus[1]->id], $schedule->menus->pluck('id')->all());
        // Hanya test case pada dua submenu terpilih yang tercakup.
        $expectedTcIds = $testCases->whereIn('menu_id', [$menus[0]->id, $menus[1]->id])->pluck('id')->all();
        $this->assertEqualsCanonicalizing($expectedTcIds, $schedule->testCases->pluck('id')->all());
    }

    public function test_user_only_sees_assigned_schedules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testerA = User::factory()->create();
        $testerB = User::factory()->create();
        [$application, $module] = $this->createModuleFixture();

        $scheduleA = $this->createCentralSchedule($application, $module, [$testerA->id]);
        $this->createCentralSchedule($application, $module, [$testerB->id]);

        RolePermissionService::saveSingleUserPermissions($testerA->id, ['/schedules'], []);
        Sanctum::actingAs($testerA);

        $response = $this->getJson('/api/schedules');
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($scheduleA->id));
        $this->assertCount(1, $ids);

        // User tidak dapat membuka jadwal milik tester lain.
        $other = QaSchedule::where('id', '!=', $scheduleA->id)->first();
        $this->getJson("/api/schedules/{$other->id}")->assertForbidden();
    }

    public function test_second_assigned_tester_can_execute(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $testerA = User::factory()->create();
        $testerB = User::factory()->create();
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$testerA->id, $testerB->id]);
        $testCase = $testCases->first();

        RolePermissionService::saveSingleUserPermissions($testerB->id, ['/schedules', '/test-cases'], ['execute_test']);
        Sanctum::actingAs($testerB);

        $this->getJson("/api/schedules/{$schedule->id}/menus")->assertOk();
        $this->getJson("/api/schedules/{$schedule->id}/test-cases?menu_id={$testCase->menu_id}")
            ->assertOk()
            ->assertJsonFragment(['test_case_code' => $testCase->test_case_code]);

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
            'actual_result' => 'Sesuai ekspektasi',
        ])->assertCreated();

        $this->assertDatabaseHas('test_executions', [
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $schedule->id,
            'tester_id' => $testerB->id,
            'status' => 'PASS',
        ]);
    }

    public function test_user_cannot_execute_before_schedule_starts_or_outside_coverage(): void
    {
        $tester = User::factory()->create();
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ]);
        // Batasi cakupan hanya ke submenu pertama.
        $schedule->menus()->sync([$menus[0]->id]);
        $schedule->testCases()->sync($testCases->where('menu_id', $menus[0]->id)->pluck('id'));

        RolePermissionService::saveSingleUserPermissions($tester->id, ['/test-cases'], ['execute_test']);
        Sanctum::actingAs($tester);

        // Test case pada submenu yang tidak tercakup → 422.
        $outsideTc = $testCases->firstWhere('menu_id', $menus[2]->id);
        $this->postJson("/api/test-cases/{$outsideTc->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertUnprocessable();

        // Jadwal belum dimulai → 403.
        $insideTc = $testCases->firstWhere('menu_id', $menus[0]->id);
        $this->postJson("/api/test-cases/{$insideTc->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('test_executions', 0);
    }

    public function test_unassigned_user_cannot_execute_even_during_active_schedule(): void
    {
        $tester = User::factory()->create();
        $other = User::factory()->create();
        [$application, $module, , $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $testCase = $testCases->first();

        RolePermissionService::saveSingleUserPermissions($other->id, ['/test-cases'], ['execute_test']);
        Sanctum::actingAs($other);

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'PASS',
            'qa_schedule_id' => $schedule->id,
        ])->assertForbidden();
    }

    public function test_progress_counts_latest_execution_per_test_case(): void
    {
        $tester = User::factory()->create();
        [$application, $module, , $testCases] = $this->createModuleFixture(2);
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $tc = $testCases->first();
        TestExecution::create([
            'test_case_id' => $tc->id,
            'qa_schedule_id' => $schedule->id,
            'tester_id' => $tester->id,
            'status' => 'FAILED',
            'executed_at' => now()->subMinutes(30),
        ]);
        TestExecution::create([
            'test_case_id' => $tc->id,
            'qa_schedule_id' => $schedule->id,
            'tester_id' => $tester->id,
            'status' => 'PASS',
            'executed_at' => now(),
        ]);

        $progress = $schedule->fresh()->progress;
        $this->assertSame(2, $progress['target']);
        $this->assertSame(1, $progress['completed']);
        $this->assertSame(1, $progress['pass']);
        $this->assertSame(0, $progress['failed']);
        $this->assertSame(1, $progress['untested']);
        $this->assertSame(50, $progress['percentage']);
    }

    public function test_expired_schedule_locks_only_its_covered_menus(): void
    {
        $tester = User::factory()->create();
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();

        // Jadwal lama mencakup hanya submenu pertama dan sudah berakhir.
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
        ]);
        $schedule->menus()->sync([$menus[0]->id]);
        $schedule->testCases()->sync($testCases->where('menu_id', $menus[0]->id)->pluck('id'));

        AutomatedReminderSetting::current()->update([
            'qa_lock_mode' => 'grace_then_lock',
            'qa_lock_minutes' => 0,
        ]);

        $lockedTc = $testCases->firstWhere('menu_id', $menus[0]->id);
        $unlockedTc = $testCases->firstWhere('menu_id', $menus[1]->id);

        // Test case pada submenu tercakup terkunci; submenu lain tetap bebas.
        $this->assertNotNull(app(QaScheduleLockService::class)->expiredScheduleForTestCase($lockedTc));
        $this->assertNull(app(QaScheduleLockService::class)->expiredScheduleForTestCase($unlockedTc));
    }

    public function test_menus_endpoint_returns_coverage_statistics(): void
    {
        $tester = User::factory()->create();
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $tc = $testCases->firstWhere('menu_id', $menus[0]->id);
        TestExecution::create([
            'test_case_id' => $tc->id,
            'qa_schedule_id' => $schedule->id,
            'tester_id' => $tester->id,
            'status' => 'PASS',
            'executed_at' => now(),
        ]);

        Sanctum::actingAs($tester);
        $response = $this->getJson("/api/schedules/{$schedule->id}/menus")->assertOk();

        $coverage = collect($response->json('menus'))->firstWhere('menu_id', $menus[0]->id);
        $this->assertNotNull($coverage);
        $this->assertSame(1, $coverage['total']);
        $this->assertSame(1, $coverage['pass']);
        $this->assertSame(100, $coverage['percentage']);
    }

    public function test_activities_endpoint_merges_logs_and_executions(): void
    {
        $tester = User::factory()->create();
        [$application, $module, $menus, $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$tester->id], [
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        $tc = $testCases->firstWhere('menu_id', $menus[0]->id);

        TestExecution::create([
            'test_case_id' => $tc->id,
            'qa_schedule_id' => $schedule->id,
            'tester_id' => $tester->id,
            'status' => 'PASS',
            'executed_at' => now(),
        ]);

        Sanctum::actingAs($tester);
        $response = $this->getJson("/api/schedules/{$schedule->id}/activities")->assertOk();

        $activities = collect($response->json('activities'));
        $this->assertTrue($activities->contains(fn ($a) => $a['type'] === 'execution' && $a['result'] === 'PASS'));
        $this->assertTrue($activities->contains(fn ($a) => $a['type'] === 'log' && $a['action'] === 'schedule_created'));
    }

    public function test_admin_can_reduce_schedule_test_case_coverage(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$application, $module, , $testCases] = $this->createModuleFixture();
        $schedule = $this->createCentralSchedule($application, $module, [$admin->id]);
        $kept = $testCases->take(1)->pluck('id')->all();

        Sanctum::actingAs($admin);
        $this->putJson("/api/schedules/{$schedule->id}/test-cases", [
            'test_case_ids' => $kept,
        ])->assertOk();

        $this->assertEqualsCanonicalizing($kept, $schedule->fresh()->testCases->pluck('id')->all());

        // Test case di luar cakupan submenu ditolak.
        $outside = TestCaseModel::create([
            'test_case_code' => 'TC-OUTSIDE-'.fake()->unique()->numberBetween(1000, 9999),
            'title' => 'Di luar cakupan',
            'application_id' => $application->id,
            'module_id' => ApplicationModule::create([
                'application_id' => $application->id,
                'name' => 'Modul Lain',
                'code' => 'MOD-LAIN-'.fake()->unique()->numberBetween(100, 999),
            ])->id,
            'expected_result' => 'x',
            'priority' => 'Low',
            'is_active' => true,
        ]);

        $this->putJson("/api/schedules/{$schedule->id}/test-cases", [
            'test_case_ids' => [$outside->id],
        ])->assertUnprocessable();
    }

    public function test_schedule_time_roundtrip_uses_utc_without_double_conversion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$application, $module] = $this->createModuleFixture();
        Sanctum::actingAs($admin);

        // 08:00 WIB = 01:00 UTC — frontend mengirim ISO UTC.
        $startUtc = '2026-11-05T01:00:00.000000Z';
        $endUtc = '2026-11-10T01:00:00.000000Z';

        $this->postJson('/api/schedules', [
            'title' => 'QA Timezone',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_scope' => 'all',
            'tester_ids' => [$admin->id],
            'start_time' => $startUtc,
            'end_time' => $endUtc,
            'priority' => 'Medium',
            'testing_type' => 'Daily',
        ])->assertCreated();

        $schedule = QaSchedule::latest('id')->first();
        $this->assertSame('2026-11-05 01:00:00', $schedule->start_time->format('Y-m-d H:i:s'));

        $show = $this->getJson("/api/schedules/{$schedule->id}")->assertOk();
        $this->assertStringStartsWith('2026-11-05T01:00:00', $show->json('schedule.start_time'));
    }

    /**
     * @return array{Application, ApplicationModule, Collection<int, ApplicationMenu>, Collection<int, TestCaseModel>}
     */
    private function createModuleFixture(int $menuCount = 3): array
    {
        $application = Application::create([
            'name' => 'Aplikasi Terpusat',
            'code' => 'APP-CENTRAL-'.fake()->unique()->numberBetween(100, 999),
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Master Data',
            'code' => 'MOD-CENTRAL-'.fake()->unique()->numberBetween(100, 999),
        ]);

        $menus = collect();
        $testCases = collect();
        for ($i = 0; $i < $menuCount; $i++) {
            $menu = ApplicationMenu::create([
                'application_id' => $application->id,
                'module_id' => $module->id,
                'name' => "Submenu {$i}",
                'code' => 'MNU-CENTRAL-'.fake()->unique()->numberBetween(1000, 9999),
                'order_index' => $i,
            ]);
            $menus->push($menu);

            $testCases->push(TestCaseModel::create([
                'test_case_code' => 'TC-CENTRAL-'.fake()->unique()->numberBetween(1000, 9999),
                'title' => "Skenario submenu {$i}",
                'application_id' => $application->id,
                'module_id' => $module->id,
                'menu_id' => $menu->id,
                'expected_result' => 'Berhasil',
                'priority' => 'Medium',
                'is_active' => true,
            ]));
        }

        return [$application, $module, $menus, $testCases];
    }

    /**
     * @param  array<int, int>  $testerIds
     * @param  array<string, mixed>  $overrides
     */
    private function createCentralSchedule(
        Application $application,
        ApplicationModule $module,
        array $testerIds,
        array $overrides = [],
    ): QaSchedule {
        $menuIds = ApplicationMenu::where('module_id', $module->id)->pluck('id')->all();

        $payload = array_merge([
            'title' => 'Jadwal Terpusat '.fake()->unique()->numberBetween(100, 999),
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_scope' => 'all',
            'tester_ids' => $testerIds,
            'start_time' => now()->subHour()->toDateTimeString(),
            'end_time' => now()->addHour()->toDateTimeString(),
            'priority' => 'Medium',
            'testing_type' => 'Daily',
            'status' => 'In Progress',
        ], $overrides);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/schedules', $payload)->assertCreated();

        return QaSchedule::latest('id')->first();
    }
}
