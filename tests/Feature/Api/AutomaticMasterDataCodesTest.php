<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AutomaticMasterDataCodesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_master_records_without_manually_provided_codes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $applicationResponse = $this->postJson('/api/applications', [
            'name' => 'SISQA',
        ])->assertCreated()
            ->assertJsonPath('application.code', 'APP-0001');

        $applicationId = $applicationResponse->json('application.id');
        $moduleResponse = $this->postJson('/api/modules', [
            'application_id' => $applicationId,
            'name' => 'Master Data',
        ])->assertCreated()
            ->assertJsonPath('module.code', 'MOD-0001');

        $moduleId = $moduleResponse->json('module.id');
        $this->postJson('/api/menus', [
            'application_id' => $applicationId,
            'module_id' => $moduleId,
            'name' => 'Manajemen Pengguna',
        ])->assertCreated()
            ->assertJsonPath('menu.code', 'MENU-0001');

        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'code' => 'APP-0001',
        ]);
        $this->assertDatabaseHas('application_modules', [
            'id' => $moduleId,
            'code' => 'MOD-0001',
        ]);
        $this->assertDatabaseHas('application_menus', [
            'module_id' => $moduleId,
            'code' => 'MENU-0001',
        ]);
    }

    public function test_ignores_client_codes_on_create_and_update(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $applicationResponse = $this->postJson('/api/applications', [
            'name' => 'SISQA',
            'code' => 'CLIENT-APP',
        ])->assertCreated()
            ->assertJsonPath('application.code', 'APP-0001');
        $applicationId = $applicationResponse->json('application.id');

        $moduleResponse = $this->postJson('/api/modules', [
            'application_id' => $applicationId,
            'name' => 'Master Data',
            'code' => 'CLIENT-MODULE',
        ])->assertCreated()
            ->assertJsonPath('module.code', 'MOD-0001');
        $moduleId = $moduleResponse->json('module.id');

        $menuResponse = $this->postJson('/api/menus', [
            'application_id' => $applicationId,
            'module_id' => $moduleId,
            'name' => 'Manajemen Pengguna',
            'code' => 'CLIENT-MENU',
        ])->assertCreated()
            ->assertJsonPath('menu.code', 'MENU-0001');
        $menuId = $menuResponse->json('menu.id');

        $this->putJson("/api/applications/{$applicationId}", [
            'name' => 'SISQA Updated',
            'code' => 'OVERRIDE-APP',
        ])->assertOk()
            ->assertJsonPath('application.code', 'APP-0001');

        $this->putJson("/api/modules/{$moduleId}", [
            'application_id' => $applicationId,
            'name' => 'Master Data Updated',
            'code' => 'OVERRIDE-MODULE',
        ])->assertOk()
            ->assertJsonPath('module.code', 'MOD-0001');

        $this->putJson("/api/menus/{$menuId}", [
            'application_id' => $applicationId,
            'module_id' => $moduleId,
            'name' => 'Manajemen Pengguna Updated',
            'code' => 'OVERRIDE-MENU',
        ])->assertOk()
            ->assertJsonPath('menu.code', 'MENU-0001');

        $this->assertDatabaseHas('applications', [
            'id' => $applicationId,
            'code' => 'APP-0001',
        ]);
        $this->assertDatabaseHas('application_modules', [
            'id' => $moduleId,
            'code' => 'MOD-0001',
        ]);
        $this->assertDatabaseHas('application_menus', [
            'id' => $menuId,
            'code' => 'MENU-0001',
        ]);
    }

    public function test_skips_codes_already_used_by_soft_deleted_applications(): void
    {
        Application::create([
            'name' => 'Aplikasi Lama',
            'code' => 'APP-0002',
        ])->delete();

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/applications', [
            'name' => 'SISQA',
        ])->assertCreated()
            ->assertJsonPath('application.code', 'APP-0003');

        $this->assertDatabaseHas('applications', [
            'name' => 'Aplikasi Lama',
            'code' => 'APP-0002',
        ]);
        $this->assertDatabaseHas('applications', [
            'name' => 'SISQA',
            'code' => 'APP-0003',
        ]);
    }
}
