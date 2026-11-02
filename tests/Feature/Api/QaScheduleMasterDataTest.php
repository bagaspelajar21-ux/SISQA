<?php

namespace Tests\Feature\Api;

use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QaScheduleMasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_cascade_lookups_return_modules_and_submenus_for_the_selected_application(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        [$application, $module, $menu] = $this->createMasterData();
        $otherApplication = Application::create([
            'name' => 'Aplikasi Lain',
            'code' => 'APP-OTHER',
        ]);
        $otherModule = ApplicationModule::create([
            'application_id' => $otherApplication->id,
            'name' => 'Modul Lain',
            'code' => 'MOD-OTHER',
        ]);
        ApplicationMenu::create([
            'application_id' => $otherApplication->id,
            'module_id' => $otherModule->id,
            'name' => 'Fitur Lain',
            'code' => 'MENU-OTHER',
        ]);
        $deletedMenu = ApplicationMenu::create([
            'application_id' => $application->id,
            'module_id' => $module->id,
            'name' => 'Fitur Dihapus',
            'code' => 'MENU-DELETED',
        ]);
        $deletedMenu->delete();

        $this->getJson("/api/cascade/modules?application_id={$application->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $module->id);

        $this->getJson("/api/cascade/menus?application_id={$application->id}&module_id={$module->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $menu->id);
    }

    public function test_creates_schedule_with_application_module_and_submenu_from_master_data(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        [$application, $module, $menu] = $this->createMasterData();

        $response = $this->postJson('/api/schedules', [
            'title' => 'Pengujian Master Data',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $menu->id,
            'testing_type' => 'Daily',
            'priority' => 'Medium',
            'start_time' => '2026-11-03 09:00:00',
            'end_time' => '2026-11-03 12:00:00',
        ])->assertCreated()
            ->assertJsonPath('schedule.application_id', $application->id)
            ->assertJsonPath('schedule.module_id', $module->id)
            ->assertJsonPath('schedule.menu_id', $menu->id);

        $this->assertDatabaseHas('qa_schedules', [
            'id' => $response->json('schedule.id'),
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $menu->id,
        ]);
    }

    public function test_rejects_a_submenu_that_does_not_belong_to_the_selected_application_and_module(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        [$application, $module] = $this->createMasterData();
        $otherApplication = Application::create([
            'name' => 'Aplikasi Lain',
            'code' => 'APP-INVALID',
        ]);
        $otherModule = ApplicationModule::create([
            'application_id' => $otherApplication->id,
            'name' => 'Modul Lain',
            'code' => 'MOD-INVALID',
        ]);
        $otherMenu = ApplicationMenu::create([
            'application_id' => $otherApplication->id,
            'module_id' => $otherModule->id,
            'name' => 'Fitur Lain',
            'code' => 'MENU-INVALID',
        ]);

        $this->postJson('/api/schedules', [
            'title' => 'Jadwal Tidak Valid',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $otherMenu->id,
            'testing_type' => 'Daily',
            'priority' => 'Medium',
            'start_time' => '2026-11-03 09:00:00',
            'end_time' => '2026-11-03 12:00:00',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['menu_id']);

        $this->assertDatabaseCount('qa_schedules', 0);
    }

    /**
     * @return array{Application, ApplicationModule, ApplicationMenu}
     */
    private function createMasterData(): array
    {
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Modul QA',
            'code' => 'MOD-QA',
        ]);
        $menu = ApplicationMenu::create([
            'application_id' => $application->id,
            'module_id' => $module->id,
            'name' => 'Fitur QA',
            'code' => 'MENU-QA',
        ]);

        return [$application, $module, $menu];
    }
}
