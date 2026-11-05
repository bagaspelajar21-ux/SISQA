<?php

namespace Tests\Feature\Api;

use App\Models\AdminReminder;
use App\Models\Application;
use App\Models\AutomatedReminderSetting;
use App\Models\QaSchedule;
use App\Models\User;
use App\Notifications\QaReminderNotification;
use Carbon\Carbon;
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
        ]);
        $this->assertNotNull(AdminReminder::findOrFail($response->json('reminder.id'))->delivered_at);
        $this->assertSame('Pengingat QA', $recipient->notifications()->sole()->data['title']);
        $this->assertSame(0, $excludedAdmin->notifications()->count());
    }

    public function test_admin_schedules_reminder_and_due_command_delivers_it(): void
    {
        $this->disableAutomatedReminders();
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
        $this->disableAutomatedReminders();
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

    public function test_due_schedule_notifies_all_assigned_testers(): void
    {
        $this->disableAutomatedReminders();
        $this->freezeTime();
        $testerA = User::factory()->create(['role' => 'user']);
        $testerB = User::factory()->create(['role' => 'user']);
        $bystander = User::factory()->create(['role' => 'user']);
        $application = Application::create([
            'name' => 'Aplikasi QA',
            'code' => 'APP-QA-MULTI',
        ]);
        $schedule = QaSchedule::create([
            'schedule_code' => 'SCH-QA-MULTI',
            'title' => 'Jadwal multi tester',
            'application_id' => $application->id,
            'tester_id' => $testerA->id,
            'start_time' => now()->subHours(2),
            'end_time' => now()->addHour(),
            'target_test_cases_count' => 0,
            'priority' => 'Medium',
            'status' => 'In Progress',
        ]);
        $schedule->testers()->sync([$testerA->id, $testerB->id]);

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame(1, $testerA->notifications()->count());
        $this->assertSame(1, $testerB->notifications()->count());
        $this->assertSame(0, $bystander->notifications()->count());
    }

    public function test_automated_reminder_still_dispatches_when_cron_is_late(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        // Cron terlambat 45 menit — reminder tetap terkirim sekali (catch-up).
        $this->travelTo(Carbon::parse('2026-11-02 08:47:00', 'Asia/Jakarta'));

        $this->artisan('notifications:dispatch-due')->assertSuccessful();
        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
        $this->assertDatabaseCount('automated_reminder_dispatches', 1);
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

    public function test_admin_can_view_and_update_automated_reminder_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/admin/reminders/automated-settings')
            ->assertOk()
            ->assertJsonPath('timezone', 'Asia/Jakarta')
            ->assertJsonPath('settings.qa_time', '08:00:00')
            ->assertJsonPath('settings.daily_report_time', '16:00:00')
            ->assertJsonPath('settings.weekly_report_enabled', true)
            ->assertJsonPath('settings.monthly_report_enabled', false);

        $this->putJson('/api/admin/reminders/automated-settings', [
            'qa_enabled' => false,
            'qa_time' => '08:30',
            'daily_report_enabled' => true,
            'daily_report_time' => '16:15',
            'weekly_report_enabled' => true,
            'weekly_report_time' => '15:30',
            'monthly_report_enabled' => true,
            'monthly_report_time' => '14:00',
            'motivation_enabled' => true,
            'motivation_message' => 'Semangat menguji!',
        ])->assertOk()
            ->assertJsonPath('settings.qa_enabled', false)
            ->assertJsonPath('settings.qa_time', '08:30')
            ->assertJsonPath('settings.monthly_report_enabled', true)
            ->assertJsonPath('settings.motivation_message', 'Semangat menguji!');

        $this->assertDatabaseHas('automated_reminder_settings', [
            'id' => 1,
            'qa_enabled' => false,
            'qa_time' => '08:30',
            'monthly_report_enabled' => true,
            'motivation_message' => 'Semangat menguji!',
        ]);
    }

    public function test_non_admin_cannot_read_or_change_automated_reminder_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->getJson('/api/admin/reminders/automated-settings')->assertForbidden();

        $this->putJson('/api/admin/reminders/automated-settings', [])->assertForbidden();

        $this->assertDatabaseCount('automated_reminder_settings', 1);
    }

    public function test_rejects_invalid_time_for_automated_reminder_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/reminders/automated-settings', [
            'qa_enabled' => true,
            'qa_time' => '25:90',
            'daily_report_enabled' => true,
            'daily_report_time' => '16:00',
            'weekly_report_enabled' => true,
            'weekly_report_time' => '16:00',
            'monthly_report_enabled' => false,
            'monthly_report_time' => '16:00',
            'motivation_enabled' => true,
            'motivation_message' => 'Semangat!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['qa_time']);
    }

    public function test_dispatches_daily_qa_reminder_once_to_active_users_at_configured_wib_time(): void
    {
        $activeUser = User::factory()->create(['role' => 'user']);
        $activeAdmin = User::factory()->create(['role' => 'admin']);
        $inactiveUser = User::factory()->create(['role' => 'user', 'is_active' => false]);
        $this->travelTo(Carbon::parse('2026-11-02 08:02:00', 'Asia/Jakarta'));

        $this->artisan('notifications:dispatch-due')->assertSuccessful();
        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $notification = $activeUser->notifications()->sole();
        $this->assertSame('Waktunya QA harian', $notification->data['title']);
        $this->assertSame('/schedules', $notification->data['url']);
        $this->assertStringContainsString('Semangat QA!', $notification->data['message']);
        $this->assertSame(1, $activeAdmin->notifications()->count());
        $this->assertSame(0, $inactiveUser->notifications()->count());
        $this->assertDatabaseCount('automated_reminder_dispatches', 1);
    }

    public function test_dispatches_weekly_report_every_saturday_and_monthly_report_only_when_enabled(): void
    {
        // Matikan pengingat QA harian agar catch-up tidak ikut terkirim.
        AutomatedReminderSetting::current()->update(['qa_enabled' => false]);
        $user = User::factory()->create(['role' => 'user']);
        $this->travelTo(Carbon::parse('2026-11-07 16:02:00', 'Asia/Jakarta'));

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $weeklyNotification = $user->notifications()
            ->where('data->title', 'Waktunya report mingguan')
            ->sole();
        $this->assertSame('/reports/weekly', $weeklyNotification->data['url']);
        $this->assertSame(0, $user->notifications()->where('data->title', 'Waktunya report bulanan')->count());

        AutomatedReminderSetting::current()->update(['monthly_report_enabled' => true]);
        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $monthlyNotification = $user->notifications()
            ->where('data->title', 'Waktunya report bulanan')
            ->sole();
        $this->assertSame('/reports/weekly', $monthlyNotification->data['url']);
        $this->assertSame(3, $user->notifications()->count());
    }

    public function test_does_not_dispatch_automated_reminders_when_they_are_disabled(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        AutomatedReminderSetting::current()->update(['qa_enabled' => false]);
        $this->travelTo(Carbon::parse('2026-11-02 08:02:00', 'Asia/Jakarta'));

        $this->artisan('notifications:dispatch-due')->assertSuccessful();

        $this->assertSame(0, $user->notifications()->count());
        $this->assertDatabaseCount('automated_reminder_dispatches', 0);
    }

    private function disableAutomatedReminders(): void
    {
        AutomatedReminderSetting::current()->update([
            'qa_enabled' => false,
            'daily_report_enabled' => false,
            'weekly_report_enabled' => false,
            'monthly_report_enabled' => false,
            'motivation_enabled' => false,
        ]);
    }
}
