<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\TestCase as QaTestCase;
use App\Models\TestExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OwnerMonitoringAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_owner_account_with_monitoring_access(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/users', [
            'name' => 'Owner QA',
            'email' => 'owner@example.test',
            'password' => 'password123',
            'role' => 'owner',
        ])->assertCreated()
            ->assertJsonPath('user.role', 'owner')
            ->assertJsonPath('user.permissions.menus', ['/', '/reports', '/reports/wa'])
            ->assertJsonPath('user.permissions.features', [
                'dashboard_all_data',
                'dashboard_exec_charts',
                'export_report',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $response->json('user.id'),
            'role' => 'owner',
        ]);

        $this->putJson('/api/users/'.$response->json('user.id'), [
            'name' => 'Owner QA Updated',
            'email' => 'owner@example.test',
            'role' => 'owner',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('user.role', 'owner');
    }

    public function test_owner_can_monitor_all_qa_results_but_cannot_change_them(): void
    {
        $this->travelTo('2026-11-03 12:00:00');
        $owner = User::factory()->create(['role' => 'owner']);
        $firstTester = User::factory()->create();
        $secondTester = User::factory()->create();
        $application = Application::create([
            'name' => 'Aplikasi Monitoring',
            'code' => 'APP-MONITOR',
        ]);
        $passedCase = $this->createTestCase($application, 'TC-OWNER-PASS');
        $failedCase = $this->createTestCase($application, 'TC-OWNER-FAIL');
        $this->createExecution($passedCase, $firstTester, 'PASS');
        $this->createExecution($failedCase, $secondTester, 'FAILED');
        Sanctum::actingAs($owner);

        $this->getJson('/api/dashboard?start_date=2026-11-03&end_date=2026-11-03')
            ->assertOk()
            ->assertJsonPath('access_scope.is_restricted', false)
            ->assertJsonPath('access_scope.can_view_all_data', true)
            ->assertJsonPath('access_scope.can_use_quick_actions', false)
            ->assertJsonPath('metrics.total_test_cases', 2)
            ->assertJsonPath('metrics.pass', 1)
            ->assertJsonPath('metrics.failed', 1);

        $this->getJson('/api/reports/summary?start_date=2026-11-03&end_date=2026-11-03')
            ->assertOk()
            ->assertJsonPath('summary.total_test_cases', 2)
            ->assertJsonPath('summary.pass', 1)
            ->assertJsonPath('summary.failed', 1);

        $this->getJson('/api/cascade/apps')->assertOk();
        $this->getJson('/api/test-cases')->assertForbidden();
        $this->postJson('/api/schedules', [])->assertForbidden();
        $this->getJson('/api/admin/qa-settings')->assertForbidden();
        $this->putJson('/api/role-permissions', [
            'menus' => ['/'],
            'features' => [],
        ])->assertForbidden();
    }

    private function createTestCase(Application $application, string $code): QaTestCase
    {
        return QaTestCase::create([
            'test_case_code' => $code,
            'title' => $code,
            'application_id' => $application->id,
            'expected_result' => 'Hasil sesuai.',
        ]);
    }

    private function createExecution(QaTestCase $testCase, User $tester, string $status): TestExecution
    {
        return TestExecution::create([
            'test_case_id' => $testCase->id,
            'tester_id' => $tester->id,
            'status' => $status,
            'executed_at' => '2026-11-03 10:00:00',
        ]);
    }
}
