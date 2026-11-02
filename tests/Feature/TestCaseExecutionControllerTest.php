<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\TestCase as QaTestCase;
use App\Models\TestExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestCaseExecutionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_execution_saves_the_feature_and_issue_description(): void
    {
        $application = Application::create([
            'name' => 'Aplikasi Pembelian',
            'code' => 'APP-PURCHASE-EXEC',
        ]);
        $testCase = QaTestCase::create([
            'test_case_code' => 'TC-PURCHASE-WAREHOUSE',
            'title' => 'Uji kolom gudang',
            'application_id' => $application->id,
            'expected_result' => 'Kolom gudang dapat diisi.',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'FAILED',
            'feature_description' => 'Menu Pembelian — kolom Gudang tidak bisa diisi.',
        ])
            ->assertCreated()
            ->assertJsonPath('execution.feature_description', 'Menu Pembelian — kolom Gudang tidak bisa diisi.');

        $this->assertDatabaseHas('test_executions', [
            'test_case_id' => $testCase->id,
            'status' => 'FAILED',
            'feature_description' => 'Menu Pembelian — kolom Gudang tidak bisa diisi.',
        ]);
        $this->assertSame(1, TestExecution::query()->count());
    }

    public function test_execution_rejects_a_feature_description_longer_than_5000_characters(): void
    {
        $application = Application::create([
            'name' => 'Aplikasi Pembelian',
            'code' => 'APP-PURCHASE-LIMIT',
        ]);
        $testCase = QaTestCase::create([
            'test_case_code' => 'TC-PURCHASE-LIMIT',
            'title' => 'Uji batas panjang keterangan',
            'application_id' => $application->id,
            'expected_result' => 'Keterangan valid.',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson("/api/test-cases/{$testCase->id}/execute", [
            'status' => 'FAILED',
            'feature_description' => str_repeat('x', 5001),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('feature_description');

        $this->assertDatabaseCount('test_executions', 0);
    }
}
