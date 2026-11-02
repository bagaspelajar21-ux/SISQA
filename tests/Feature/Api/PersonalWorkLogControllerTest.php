<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PersonalWorkLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_personal_work_logs_are_requested_without_authentication(): void
    {
        $this->getJson('/api/work-logs?week_of=2026-10-31')
            ->assertUnauthorized();
    }

    public function test_lists_only_the_authenticated_users_logs_for_the_selected_week(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $ownLog = $user->workLogs()->create([
            'work_date' => '2026-10-26',
            'description' => 'Menyusun agenda',
        ]);
        $otherUser->workLogs()->create([
            'work_date' => '2026-10-27',
            'description' => 'Catatan pribadi pengguna lain',
        ]);
        $user->workLogs()->create([
            'work_date' => '2026-11-02',
            'description' => 'Pekerjaan minggu berikutnya',
        ]);

        $this->getJson('/api/work-logs?week_of=2026-10-31')
            ->assertOk()
            ->assertJsonPath('week.start_date', '2026-10-26')
            ->assertJsonPath('week.end_date', '2026-11-01')
            ->assertJsonPath('work_logs.0.id', $ownLog->id)
            ->assertJsonMissing(['description' => 'Catatan pribadi pengguna lain']);
    }

    public function test_creates_a_personal_work_log_for_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/work-logs', [
            'week_of' => '2026-10-31',
            'work_date' => '2026-10-28',
            'description' => 'Menyelesaikan bahan presentasi',
            'user_id' => User::factory()->create()->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('work_log.work_date', '2026-10-28')
            ->assertJsonPath('work_log.description', 'Menyelesaikan bahan presentasi')
            ->assertJsonMissingPath('work_log.user_id');

        $workLog = WorkLog::query()->sole();
        $this->assertSame($user->id, $workLog->user_id);
    }

    public function test_updates_and_deletes_the_authenticated_users_work_log(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $workLog = $user->workLogs()->create([
            'work_date' => '2026-10-28',
            'description' => 'Agenda awal',
        ]);

        $this->putJson("/api/work-logs/{$workLog->id}", [
            'week_of' => '2026-10-31',
            'work_date' => '2026-10-29',
            'description' => 'Agenda yang sudah diperbaiki',
        ])->assertOk()
            ->assertJsonPath('work_log.work_date', '2026-10-29')
            ->assertJsonPath('work_log.description', 'Agenda yang sudah diperbaiki');

        $this->assertDatabaseHas('personal_work_logs', [
            'id' => $workLog->id,
            'user_id' => $user->id,
            'work_date' => '2026-10-29',
            'description' => 'Agenda yang sudah diperbaiki',
        ]);

        $this->deleteJson("/api/work-logs/{$workLog->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_work_logs', ['id' => $workLog->id]);
    }

    public function test_returns_404_when_a_user_changes_or_deletes_another_users_log(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        Sanctum::actingAs($user);
        $workLog = $owner->workLogs()->create([
            'work_date' => '2026-10-28',
            'description' => 'Catatan yang bukan milik pemohon',
        ]);

        $this->putJson("/api/work-logs/{$workLog->id}", [
            'week_of' => '2026-10-31',
            'work_date' => '2026-10-29',
            'description' => 'Perubahan tidak diizinkan',
        ])->assertNotFound();

        $this->deleteJson("/api/work-logs/{$workLog->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('personal_work_logs', [
            'id' => $workLog->id,
            'user_id' => $owner->id,
            'description' => 'Catatan yang bukan milik pemohon',
        ]);
    }

    public function test_returns_422_when_work_log_date_is_outside_the_selected_week(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/work-logs', [
            'week_of' => '2026-10-31',
            'work_date' => '2026-10-25',
            'description' => 'Pekerjaan di minggu sebelumnya',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['work_date']);

        $this->assertDatabaseCount('personal_work_logs', 0);
    }

    public function test_returns_422_when_work_log_description_contains_only_whitespace(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/work-logs', [
            'week_of' => '2026-10-31',
            'work_date' => '2026-10-28',
            'description' => " \t\n ",
        ])->assertUnprocessable()
            ->assertJsonPath('errors.description.0', 'Uraian pekerjaan wajib diisi.');

        $this->assertDatabaseCount('personal_work_logs', 0);
    }

    public function test_downloads_a_pdf_containing_only_the_users_personal_work_logs(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $user->workLogs()->create([
            'work_date' => '2026-10-28',
            'description' => 'Menyiapkan bahan rapat',
        ]);
        $otherUser->workLogs()->create([
            'work_date' => '2026-10-28',
            'description' => 'Catatan pengguna lain',
        ]);

        $response = $this->get('/api/work-logs/export/pdf?week_of=2026-10-31');

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
        $this->assertStringContainsString('%PDF', $response->getContent());
    }
}
