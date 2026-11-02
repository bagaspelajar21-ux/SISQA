<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionAccessTest extends TestCase
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

    public function test_returns_403_when_user_opens_a_menu_not_granted_by_admin(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/reports'], []);
        Sanctum::actingAs($user);

        $this->getJson('/api/work-logs?week_of=2026-10-31')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ke menu ini tidak diberikan oleh Administrator.');
    }

    public function test_returns_403_when_user_opens_a_feature_not_granted_by_admin(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], []);
        Sanctum::actingAs($user);

        $this->get('/api/test-cases/excel-template')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ke fitur ini tidak diberikan oleh Administrator.');
    }

    public function test_returns_403_when_user_opens_an_evidence_file_without_its_menu_access(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/reports'], []);
        Sanctum::actingAs($user);

        $this->get('/api/files/flow/999/download')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ke menu ini tidak diberikan oleh Administrator.');
    }

    public function test_returns_401_when_evidence_file_is_requested_without_authentication(): void
    {
        $this->getJson('/api/files/flow/999/download')
            ->assertUnauthorized();
    }

    public function test_returns_403_when_user_creates_a_test_case_without_the_feature_permission(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions($user->id, ['/test-cases'], ['execute_test']);
        Sanctum::actingAs($user);

        $this->postJson('/api/test-cases', [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ke fitur ini tidak diberikan oleh Administrator.');
    }

    public function test_returns_403_when_user_uses_write_and_export_features_not_granted_by_admin(): void
    {
        $user = User::factory()->create();
        RolePermissionService::saveSingleUserPermissions(
            $user->id,
            ['/schedules', '/bugs', '/retests', '/flows', '/reports'],
            []
        );
        Sanctum::actingAs($user);

        $this->postJson('/api/schedules', [])->assertForbidden();
        $this->postJson('/api/bugs', [])->assertForbidden();
        $this->postJson('/api/retests', [])->assertForbidden();
        $this->postJson('/api/flows', [])->assertForbidden();
        $this->get('/api/reports/export/excel')->assertForbidden();
    }

    public function test_returns_422_when_admin_assigns_an_unknown_menu_or_feature(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/role-permissions', [
            'menus' => ['/not-a-real-menu'],
            'features' => ['not_a_real_feature'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['menus.0', 'features.0']);
    }
}
