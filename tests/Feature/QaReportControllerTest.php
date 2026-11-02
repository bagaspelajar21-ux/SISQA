<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\TestCase as QaTestCase;
use App\Models\TestExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class QaReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_includes_feature_description_application_menu_and_qa_status_in_all_formats(): void
    {
        $this->travelTo('2026-10-10 10:00:00');
        $tester = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $application = Application::create([
            'name' => 'Aplikasi Pembelian',
            'code' => 'APP-PURCHASE-REPORT',
        ]);
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Transaksi',
            'code' => 'MOD-PURCHASE-REPORT',
        ]);
        $menu = ApplicationMenu::create([
            'application_id' => $application->id,
            'module_id' => $module->id,
            'name' => 'Pembelian',
            'code' => 'MENU-PURCHASE-REPORT',
        ]);
        $testCase = QaTestCase::create([
            'test_case_code' => 'TC-PURCHASE-REPORT',
            'title' => 'Input gudang pada pembelian',
            'application_id' => $application->id,
            'module_id' => $module->id,
            'menu_id' => $menu->id,
            'expected_result' => 'Kolom gudang dapat diisi.',
        ]);
        TestExecution::create([
            'test_case_id' => $testCase->id,
            'tester_id' => $tester->id,
            'status' => 'FAILED',
            'feature_description' => 'Kolom Gudang tidak bisa diisi.',
            'actual_result' => 'Kolom tidak menerima input.',
            'executed_at' => now(),
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('executions.0.test_case.application.name', 'Aplikasi Pembelian')
            ->assertJsonPath('executions.0.test_case.menu.name', 'Pembelian')
            ->assertJsonPath('executions.0.status', 'FAILED')
            ->assertJsonPath('executions.0.feature_description', 'Kolom Gudang tidak bisa diisi.')
            ->assertJsonPath('module_progress.0.application_name', 'Aplikasi Pembelian')
            ->assertJsonPath('module_progress.0.menu_name', 'Pembelian')
            ->assertJsonPath('module_progress.0.feature_description', 'Kolom Gudang tidak bisa diisi.');

        $excelResponse = $this->get('/api/reports/export/excel')->assertOk();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'qa-report-');

        try {
            file_put_contents($temporaryPath, $excelResponse->streamedContent());
            $spreadsheet = IOFactory::load($temporaryPath);
            $executionSheet = $spreadsheet->getSheetByName('Ringkasan QA');
            $moduleSheet = $spreadsheet->getSheetByName('Hasil QA Modul');

            $this->assertNotNull($executionSheet);
            $this->assertSame('Status QA', $executionSheet->getCell('F11')->getValue());
            $this->assertSame('Menu', $executionSheet->getCell('J11')->getValue());
            $this->assertSame('Fitur yang Diuji / Keterangan', $executionSheet->getCell('K11')->getValue());
            $this->assertSame('Pembelian', $executionSheet->getCell('J12')->getValue());
            $this->assertSame('Kolom Gudang tidak bisa diisi.', $executionSheet->getCell('K12')->getValue());
            $this->assertNotNull($moduleSheet);
            $this->assertSame('Aplikasi', $moduleSheet->getCell('G5')->getValue());
            $this->assertSame('Menu', $moduleSheet->getCell('H5')->getValue());
            $this->assertSame('Kolom Gudang tidak bisa diisi.', $moduleSheet->getCell('I6')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }

        $this->get('/api/reports/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
