<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Application;
use App\Models\ApplicationModule;
use App\Models\Bug;
use App\Models\QaSchedule;
use App\Models\Retest;
use App\Models\TestCase as QaTestCase;
use App\Models\TestExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Tests\TestCase;

class QaDateRangeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_filter_includes_any_schedule_overlapping_the_selected_dates(): void
    {
        $this->travelTo('2026-10-01 09:00:00');
        $user = User::factory()->create(['role' => 'admin']);
        $application = $this->createTestApplication();
        $overlappingSchedule = $this->createSchedule($application, $user, 'QA-OVERLAP', '2026-10-09 23:00:00', '2026-10-10 02:00:00');
        $this->createSchedule($application, $user, 'QA-OUTSIDE', '2026-10-11 09:00:00', '2026-10-11 12:00:00');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/schedules?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $overlappingSchedule->id);

        $this->getJson('/api/schedules/calendar?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_test_management_filter_uses_schedule_dates_and_activity_dates_without_a_schedule(): void
    {
        $this->travelTo('2026-10-01 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $tester = User::factory()->create();
        $application = $this->createTestApplication();

        $matchingSchedule = $this->createSchedule($application, $admin, 'QA-MATCH', '2026-10-09 22:00:00', '2026-10-10 02:00:00');
        $matchingCase = $this->createTestCase($application, 'TC-MATCH');
        $matchingCase->schedules()->attach($matchingSchedule);
        $this->createExecution($matchingCase, $tester, $matchingSchedule, '2026-10-15 10:00:00');

        $outsideSchedule = $this->createSchedule($application, $admin, 'QA-OUTSIDE', '2026-10-12 09:00:00', '2026-10-12 11:00:00');
        $outsideCase = $this->createTestCase($application, 'TC-OUTSIDE');
        $outsideCase->schedules()->attach($outsideSchedule);
        $this->createExecution($outsideCase, $tester, null, '2026-10-10 09:00:00');

        $unscheduledCase = $this->createTestCase($application, 'TC-UNSCHEDULED');
        $this->createExecution($unscheduledCase, $tester, null, '2026-10-10 10:00:00');
        $this->createTestCase($application, 'TC-OLD');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/test-cases?start_date=2026-10-10&end_date=2026-10-10')->assertOk();
        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->all();
        $expectedIds = collect([$matchingCase->id, $unscheduledCase->id])->sort()->values()->all();

        $this->assertSame($expectedIds, $returnedIds);
        $this->assertSame('QA-MATCH', collect($response->json('data'))->firstWhere('id', $matchingCase->id)['schedules'][0]['schedule_code']);

        $this->getJson('/api/dashboard?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('metrics.total_test_cases', 2)
            ->assertJsonPath('metrics.pass', 2);

        $this->getJson('/api/reports/summary?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('summary.total_test_cases', 2)
            ->assertJsonPath('summary.pass', 2);
    }

    public function test_bug_filter_uses_linked_schedule_dates_and_report_dates_without_a_schedule(): void
    {
        $this->travelTo('2026-10-01 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->createTestApplication();
        $matchingSchedule = $this->createSchedule($application, $admin, 'BUG-MATCH', '2026-10-09 22:00:00', '2026-10-10 02:00:00');
        $outsideSchedule = $this->createSchedule($application, $admin, 'BUG-OUTSIDE', '2026-10-12 09:00:00', '2026-10-12 11:00:00');

        $matchingBug = $this->createBug($application, $admin, 'BUG-MATCHED', $matchingSchedule, '2026-10-01');
        $this->createBug($application, $admin, 'BUG-OUTSIDE', $outsideSchedule, '2026-10-10');
        $unscheduledBug = $this->createBug($application, $admin, 'BUG-UNSCHEDULED', null, '2026-10-10');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/bugs?start_date=2026-10-10&end_date=2026-10-10')->assertOk();
        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$matchingBug->id, $unscheduledBug->id])->sort()->values()->all(), $returnedIds);
        $this->assertSame('BUG-MATCH', collect($response->json('data'))->firstWhere('id', $matchingBug->id)['schedule']['schedule_code']);

        $this->getJson('/api/dashboard?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('metrics.total_active_bugs', 2);

        $this->getJson('/api/reports/summary?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('summary.total_bugs', 2);
    }

    public function test_retest_filter_uses_linked_schedule_dates_and_retest_dates_without_a_schedule(): void
    {
        $this->travelTo('2026-10-01 09:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->createTestApplication();
        $matchingSchedule = $this->createSchedule($application, $admin, 'RETEST-MATCH', '2026-10-09 22:00:00', '2026-10-10 02:00:00');
        $outsideSchedule = $this->createSchedule($application, $admin, 'RETEST-OUTSIDE', '2026-10-12 09:00:00', '2026-10-12 11:00:00');

        $matchingBug = $this->createBug($application, $admin, 'BUG-RETEST-MATCH', $matchingSchedule, '2026-10-01');
        $outsideBug = $this->createBug($application, $admin, 'BUG-RETEST-OUTSIDE', $outsideSchedule, '2026-10-10');
        $matchingRetest = $this->createRetest($matchingBug, $admin, $matchingSchedule, '2026-10-01 08:00:00');
        $this->createRetest($outsideBug, $admin, $outsideSchedule, '2026-10-10 08:00:00');

        $unscheduledBug = $this->createBug($application, $admin, 'BUG-RETEST-UNSCHEDULED', null, '2026-10-01');
        $unscheduledRetest = $this->createRetest($unscheduledBug, $admin, null, '2026-10-10 08:00:00');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/retests?start_date=2026-10-10&end_date=2026-10-10')->assertOk();
        $returnedIds = collect($response->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$matchingRetest->id, $unscheduledRetest->id])->sort()->values()->all(), $returnedIds);

        $this->getJson('/api/dashboard?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('metrics.waiting_retests', 0);

        $this->getJson('/api/reports/summary?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('summary.total_retests', 2);
    }

    public function test_activity_log_filter_includes_both_date_boundaries(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $before = $this->createActivityLog($admin, '2026-10-09 23:59:59');
        $inside = $this->createActivityLog($admin, '2026-10-10 12:00:00');
        $after = $this->createActivityLog($admin, '2026-10-11 00:00:00');
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/activity-logs?start_date=2026-10-10&end_date=2026-10-10')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame($inside->id, $response->json('data.0.id'));
        $this->assertNotSame($before->id, $response->json('data.0.id'));
        $this->assertNotSame($after->id, $response->json('data.0.id'));
    }

    public function test_date_filter_rejects_invalid_format_and_reversed_ranges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->getJson('/api/schedules?start_date=not-a-date')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');

        $this->getJson('/api/bugs?start_date=2026-10-11&end_date=2026-10-10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');

        $this->getJson('/api/schedules?end_date=2026-10-10')->assertOk();
    }

    public function test_qa_report_includes_per_module_status_totals_in_preview_and_exports(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $tester = User::factory()->create();
        $application = $this->createTestApplication();
        $module = ApplicationModule::create([
            'application_id' => $application->id,
            'name' => 'Penjualan',
            'code' => 'PENJUALAN',
        ]);
        $passedCase = $this->createTestCase($application, 'TC-MODULE-PASS');
        $passedCase->update(['module_id' => $module->id]);
        $this->createExecution($passedCase, $tester, null, '2026-10-10 09:00:00', 'PASS');
        $failedCase = $this->createTestCase($application, 'TC-MODULE-FAIL');
        $failedCase->update(['module_id' => $module->id]);
        $this->createExecution($failedCase, $tester, null, '2026-10-10 10:00:00', 'FAILED');
        $pendingCase = $this->createTestCase($application, 'TC-MODULE-PENDING');
        $pendingCase->update(['module_id' => $module->id]);
        $this->createExecution($pendingCase, $tester, null, '2026-10-10 11:00:00', 'PENDING');
        $untestedCase = $this->createTestCase($application, 'TC-MODULE-NOT-TESTED');
        $untestedCase->update(['module_id' => $module->id]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/reports/summary')
            ->assertOk()
            ->assertJsonPath('module_progress.0.module_name', 'Penjualan')
            ->assertJsonPath('module_progress.0.total_tc', 4)
            ->assertJsonPath('module_progress.0.passed_tc', 1)
            ->assertJsonPath('module_progress.0.failed_tc', 1)
            ->assertJsonPath('module_progress.0.not_tested_tc', 2)
            ->assertJsonPath('module_progress_totals.total_tc', 4)
            ->assertJsonPath('module_progress_totals.passed_tc', 1)
            ->assertJsonPath('module_progress_totals.failed_tc', 1)
            ->assertJsonPath('module_progress_totals.not_tested_tc', 2);

        $excelResponse = $this->get('/api/reports/export/excel')->assertOk();
        $temporaryPath = tempnam(sys_get_temp_dir(), 'qa-report-');

        try {
            file_put_contents($temporaryPath, $excelResponse->streamedContent());
            $spreadsheet = IOFactory::load($temporaryPath);
            $moduleSheet = $spreadsheet->getSheetByName('Hasil QA Modul');

            $this->assertNotNull($moduleSheet);
            $this->assertSame('HASIL QA PER MODUL', $moduleSheet->getCell('A1')->getValue());
            $this->assertSame('No', $moduleSheet->getCell('A5')->getValue());
            $this->assertSame(1, $moduleSheet->getCell('A6')->getValue());
            $this->assertSame('Penjualan', $moduleSheet->getCell('B6')->getValue());
            $this->assertSame(4, $moduleSheet->getCell('C6')->getValue());
            $this->assertSame(1, $moduleSheet->getCell('D6')->getValue());
            $this->assertSame(1, $moduleSheet->getCell('E6')->getValue());
            $this->assertSame(2, $moduleSheet->getCell('F6')->getValue());
            $this->assertSame('TOTAL', $moduleSheet->getCell('B7')->getValue());
            $this->assertSame(8.0, $moduleSheet->getColumnDimension('A')->getWidth());
            $this->assertSame(
                Alignment::HORIZONTAL_CENTER,
                $moduleSheet->getStyle('A6')->getAlignment()->getHorizontal(),
            );
            $this->assertSame('RINCIAN HASIL EKSEKUSI PENGUJIAN', $spreadsheet->getSheetByName('Ringkasan QA')->getCell('A10')->getValue());
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

    private function createTestApplication(): Application
    {
        return Application::create([
            'name' => 'Filter Test Application',
            'code' => 'FILTER-APP',
        ]);
    }

    private function createSchedule(Application $application, User $tester, string $code, string $start, string $end): QaSchedule
    {
        return QaSchedule::create([
            'schedule_code' => $code,
            'title' => $code,
            'application_id' => $application->id,
            'tester_id' => $tester->id,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'Scheduled',
        ]);
    }

    private function createTestCase(Application $application, string $code): QaTestCase
    {
        return QaTestCase::create([
            'test_case_code' => $code,
            'title' => $code,
            'application_id' => $application->id,
            'expected_result' => 'Expected result',
        ]);
    }

    private function createExecution(
        QaTestCase $testCase,
        User $tester,
        ?QaSchedule $schedule,
        string $executedAt,
        string $status = 'PASS',
    ): TestExecution {
        return TestExecution::create([
            'test_case_id' => $testCase->id,
            'qa_schedule_id' => $schedule?->id,
            'tester_id' => $tester->id,
            'status' => $status,
            'executed_at' => $executedAt,
        ]);
    }

    private function createBug(Application $application, User $reporter, string $code, ?QaSchedule $schedule, string $reportDate): Bug
    {
        return Bug::create([
            'bug_code' => $code,
            'title' => $code,
            'application_id' => $application->id,
            'qa_schedule_id' => $schedule?->id,
            'description' => 'Issue description',
            'reporter_id' => $reporter->id,
            'report_date' => $reportDate,
            'severity' => 'Minor',
            'priority' => 'Medium',
            'status' => 'Open',
        ]);
    }

    private function createRetest(Bug $bug, User $tester, ?QaSchedule $schedule, string $retestDate): Retest
    {
        return Retest::create([
            'bug_id' => $bug->id,
            'qa_schedule_id' => $schedule?->id,
            'application_id' => $bug->application_id,
            'tester_id' => $tester->id,
            'retest_date' => $retestDate,
            'actual_result' => 'Retest result',
            'status' => 'Passed',
        ]);
    }

    private function createActivityLog(User $user, string $createdAt): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'viewed',
            'module' => 'Test',
            'description' => 'Activity',
            'result' => 'Success',
            'created_at' => $createdAt,
        ]);
    }
}
