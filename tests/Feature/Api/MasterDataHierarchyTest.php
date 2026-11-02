<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\TestCase as QaTestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_modules_and_submenus_by_application(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $selectedApplication = Application::create([
            'name' => 'Aplikasi Terpilih',
            'code' => 'APP-FILTER-1',
        ]);
        $otherApplication = Application::create([
            'name' => 'Aplikasi Lain',
            'code' => 'APP-FILTER-2',
        ]);
        $selectedModule = ApplicationModule::create([
            'application_id' => $selectedApplication->id,
            'name' => 'Modul Terpilih',
            'code' => 'MOD-FILTER-1',
        ]);
        ApplicationModule::create([
            'application_id' => $otherApplication->id,
            'name' => 'Modul Lain',
            'code' => 'MOD-FILTER-2',
        ]);
        $selectedMenu = ApplicationMenu::create([
            'application_id' => $selectedApplication->id,
            'module_id' => $selectedModule->id,
            'name' => 'Sub-menu Terpilih',
            'code' => 'MENU-FILTER-1',
        ]);
        $otherModule = ApplicationModule::query()
            ->where('application_id', $otherApplication->id)
            ->firstOrFail();
        ApplicationMenu::create([
            'application_id' => $otherApplication->id,
            'module_id' => $otherModule->id,
            'name' => 'Sub-menu Lain',
            'code' => 'MENU-FILTER-2',
        ]);

        $this->getJson("/api/modules?application_id={$selectedApplication->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $selectedModule->id);

        $this->getJson("/api/menus?application_id={$selectedApplication->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $selectedMenu->id);
    }

    public function test_deleting_a_module_soft_deletes_its_submenus_only(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $application = Application::create([
            'name' => 'Aplikasi',
            'code' => 'APP-MODULE-DELETE',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul Dihapus',
            'code' => 'MOD-DELETE',
        ]);
        $menu = ApplicationMenu::create([
            'application_id' => $application->id,
            'module_id' => $module->id,
            'name' => 'Sub-menu Ikut Dihapus',
            'code' => 'MENU-DELETE',
        ]);

        $this->deleteJson("/api/modules/{$module->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Modul berhasil dihapus beserta 1 sub-menu.');

        $this->assertSoftDeleted('application_modules', ['id' => $module->id]);
        $this->assertSoftDeleted('application_menus', ['id' => $menu->id]);
        $this->assertModelExists($application);
    }

    public function test_deleting_an_application_soft_deletes_its_modules_and_submenus_without_removing_qa_records(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $application = Application::create([
            'name' => 'Aplikasi Dihapus',
            'code' => 'APP-DELETE',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul Ikut Dihapus',
            'code' => 'MOD-APP-DELETE',
        ]);
        $menu = ApplicationMenu::create([
            'application_id' => $application->id,
            'module_id' => $module->id,
            'name' => 'Sub-menu Ikut Dihapus',
            'code' => 'MENU-APP-DELETE',
        ]);
        $testCase = QaTestCase::create([
            'test_case_code' => 'TC-KEEP',
            'title' => 'QA tetap tersimpan',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $menu->id,
            'expected_result' => 'Data tetap tersedia.',
        ]);

        $this->deleteJson("/api/applications/{$application->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Aplikasi berhasil dihapus beserta 1 modul dan 1 sub-menu.');

        $this->assertSoftDeleted('applications', ['id' => $application->id]);
        $this->assertSoftDeleted('application_modules', ['id' => $module->id]);
        $this->assertSoftDeleted('application_menus', ['id' => $menu->id]);
        $this->assertDatabaseHas('test_cases', [
            'id' => $testCase->id,
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $menu->id,
        ]);
    }
}
