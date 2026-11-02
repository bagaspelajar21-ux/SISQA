<?php

namespace Tests\Feature\Api;

use App\Models\AdminReminder;
use App\Models\Application;
use App\Models\QaSchedule;
use App\Models\User;
use App\Notifications\QaReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminReminderControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_notifications_are_requested_without_authentication(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();
    }

    public function test_user_can_only_read_and_mark_their_own_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $user->notify(new QaReminderNotification('QA', 'Jangan lupa QA.'));
        $otherUser->notify(new QaReminderNotification('Privat', 'Notifikasi privat.'));
        $otherNotification = $otherUser->notifications()->firstOrFail();
        Sanctum::actingAs($user);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.data.title', 'QA')
            ->assertJsonMissing(['title' => 'Privat']);

        $this->patchJson("/api/notifications/{$otherNotification->id}/read")
            ->assertNotFound();

        $this->patchJson("/api/notifications/{$user->notifications()->firstOrFail()->id}/read")
            ->assertOk();

        $this->assertNotNull($user->notifications()->firstOrFail()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_admin_sends_due_reminder_only_to_the_selected_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recipient = User::factory()->create(['role' => 'user']);
        $excludedAdmin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/reminders', [
            'title' => 'Pengingat QA',
            'message' => 'Jangan lupa QA hari ini.',
            'recipient_type' => 'role',
            'recipient_role' => 'user',
            'scheduled_at' => now()->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pengingat berhasil dikirim.')
            ->assertJsonPath('reminder.recipient_type', 'role');

        $this->assertDatabaseHas('admin_reminders', [
            'id' => $response->json('reminder.id'),
            'created_by' => $admin->id,
            'delivered_at' => now(),
        ]);
        $this->assertSame('Pengingat QA', $recipient->notifications()->sole()->data['title']);
        $this->assertSame(0, $excludedAdmin->notifications()->count());
    }

    public function test_admin_schedules_reminder_and_due_command_delivers_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $recipient = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($admin);
        $scheduledAt = now()->addMinutes(2);

        $response = $this->postJson('/api/admin/reminders', [
            'title' => 'Pengingat report',
            'message' => 'Jangan lupa kirim report.',
            'recipient_type' => 'user',
            'recipient_user_id' => $recipient->id,
            'scheduled_at' => $scheduledAt->toIso8601String(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Pengingat berhasil dijadwalkan.');
        $this->assertDatabaseHas('admin_reminders', [
            'id' => $response->json('reminder.id'),
            'delivered_at' => null,
        ]);
        $this->assertSame(0, $recipient->notifications()->count());

        $this->travelTo($scheduledAt->copy()->addMinute());
        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame('Pengingat report', $recipient->notifications()->sole()->data['title']);
        $this->assertNotNull(AdminReminder::findOrFail($response->json('reminder.id'))->delivered_at);
    }

    public function test_due_schedule_sends_start_and_end_notifications_once_and_marks_it_overdue(): void
    {
        $this->freezeTime();
        $tester = User::factory()->create(['role' => 'user']);
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-NOTIFY',
        ]);
        $schedule = QaSchedule::create([
            'schedule_code' => 'SCH-QA-NOTIFY',
            'title' => 'Jadwal QA harian',
            'application_id' => $application->id,
            'tester_id' => $tester->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->subMinute(),
            'target_test_cases_count' => 0,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame(2, $tester->notifications()->count());
        $this->assertSame('Overdue', $schedule->fresh()->status);
        $this->assertNotNull($schedule->fresh()->start_notification_sent_at);
        $this->assertNotNull($schedule->fresh()->end_notification_sent_at);

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame(2, $tester->notifications()->count());
    }

    public function test_non_admin_cannot_create_a_reminder(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->postJson('/api/admin/reminders', [
            'title' => 'Pengingat',
            'message' => 'Jangan lupa QA.',
            'recipient_type' => 'all',
            'scheduled_at' => now()->toIso8601String(),
        ])->assertForbidden();

        $this->assertDatabaseCount('admin_reminders', 0);
    }
}
