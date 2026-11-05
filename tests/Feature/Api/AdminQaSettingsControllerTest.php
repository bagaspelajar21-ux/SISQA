<?php

namespace Tests\Feature\Api;

use App\Models\AutomatedReminderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminQaSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_admins_cannot_manage_qa_settings(): void
    {
        $this->getJson('/api/admin/qa-settings')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->getJson('/api/admin/qa-settings')->assertForbidden();
        $this->putJson('/api/admin/qa-settings', [])->assertForbidden();
        $this->postJson('/api/admin/qa-settings/motivation-image', [])->assertForbidden();
    }

    public function test_admin_can_save_lock_policy_and_daily_motivation_quotes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/admin/qa-settings')
            ->assertOk()
            ->assertJsonPath('settings.qa_lock_mode', 'grace_then_lock')
            ->assertJsonPath('settings.qa_lock_minutes', 0)
            ->assertJsonPath('settings.daily_motivation_enabled', true);

        $this->putJson('/api/admin/qa-settings', [
            'qa_lock_mode' => 'lock_for_duration',
            'qa_lock_minutes' => 15,
            'daily_motivation_enabled' => true,
            'daily_motivations' => [
                'Teliti hari ini, kualitas meningkat esok.',
                'Setiap temuan bug membantu produk lebih baik.',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('settings.qa_lock_mode', 'lock_for_duration')
            ->assertJsonPath('settings.qa_lock_minutes', 15)
            ->assertJsonPath('settings.daily_motivations.1.text', 'Setiap temuan bug membantu produk lebih baik.')
            ->assertJsonPath('settings.daily_motivations.1.author', null);

        $this->assertSame('lock_for_duration', AutomatedReminderSetting::current()->qa_lock_mode);
        $this->assertSame(15, AutomatedReminderSetting::current()->qa_lock_minutes);

        $this->putJson('/api/admin/qa-settings', [
            'qa_lock_mode' => 'follow_schedule',
            'qa_lock_minutes' => 0,
            'daily_motivation_enabled' => true,
            'daily_motivations' => ['Semangat menguji dengan teliti.'],
        ])
            ->assertOk()
            ->assertJsonPath('settings.qa_lock_mode', 'follow_schedule');
    }

    public function test_temporary_lock_requires_a_positive_duration(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/qa-settings', [
            'qa_lock_mode' => 'lock_for_duration',
            'qa_lock_minutes' => 0,
            'daily_motivation_enabled' => true,
            'daily_motivations' => ['Semangat menguji dengan teliti.'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qa_lock_minutes');
    }

    public function test_admin_can_upload_and_delete_daily_motivation_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/admin/qa-settings/motivation-image', [
            'image' => UploadedFile::fake()->image('motivation.png'),
        ])->assertOk()
            ->assertJsonPath('message', 'Desain motivasi berhasil diunggah.');

        $oldImagePath = AutomatedReminderSetting::current()->daily_motivation_image_path;
        $this->assertNotNull($oldImagePath);
        Storage::disk('public')->assertExists($oldImagePath);

        $uploadResponse = $this->postJson('/api/admin/qa-settings/motivation-image', [
            'image' => UploadedFile::fake()->image('motivation-new.png'),
        ])->assertOk()
            ->assertJsonPath('message', 'Desain motivasi berhasil diunggah.');
        $imagePath = AutomatedReminderSetting::current()->daily_motivation_image_path;
        $this->assertNotNull($imagePath);
        $this->assertNotSame($oldImagePath, $imagePath);
        Storage::disk('public')->assertMissing($oldImagePath);
        Storage::disk('public')->assertExists($imagePath);
        $uploadResponse->assertJsonPath('image_url', '/storage/'.$imagePath);

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/daily-motivation/today')
            ->assertOk()
            ->assertJsonPath('image_url', '/storage/'.$imagePath);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));
        $this->deleteJson('/api/admin/qa-settings/motivation-image')
            ->assertOk()
            ->assertJsonPath('message', 'Desain motivasi berhasil dihapus.');

        $this->assertNull(AutomatedReminderSetting::current()->daily_motivation_image_path);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_admin_cannot_upload_a_non_image_for_daily_motivation(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->postJson('/api/admin/qa-settings/motivation-image', [
            'image' => UploadedFile::fake()->create('motivation.txt', 10, 'text/plain'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertNull(AutomatedReminderSetting::current()->daily_motivation_image_path);
        Storage::disk('public')->assertDirectoryEmpty('daily-motivation');
    }

    public function test_daily_motivation_rotates_by_wib_calendar_day(): void
    {
        AutomatedReminderSetting::current()->update([
            'daily_motivations' => ['Motivasi hari pertama.', 'Motivasi hari berikutnya.'],
        ]);
        Sanctum::actingAs(User::factory()->create());
        $this->travelTo('2026-03-10 09:00:00');

        $firstDay = $this->getJson('/api/daily-motivation/today')
            ->assertOk()
            ->assertJsonPath('date', '2026-03-10')
            ->json('motivation');

        $this->travelTo('2026-03-11 00:01:00');

        $secondDay = $this->getJson('/api/daily-motivation/today')
            ->assertOk()
            ->assertJsonPath('date', '2026-03-11')
            ->json('motivation');

        $this->assertNotSame($firstDay, $secondDay);
    }

    public function test_daily_motivation_supports_author_attribution(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/qa-settings', [
            'qa_lock_mode' => 'grace_then_lock',
            'qa_lock_minutes' => 0,
            'daily_motivation_enabled' => true,
            'daily_motivations' => [
                'Kualitas adalah tanggung jawab bersama. | Tim QA',
                ['text' => 'Detail kecil, dampak besar.', 'author' => 'QA Lead'],
            ],
        ])->assertOk();

        $settings = AutomatedReminderSetting::current();
        $this->assertSame('Tim QA', $settings->daily_motivations[0]['author']);
        $this->assertSame('Kualitas adalah tanggung jawab bersama.', $settings->daily_motivations[0]['text']);
        $this->assertSame('QA Lead', $settings->daily_motivations[1]['author']);

        Sanctum::actingAs(User::factory()->create());
        $response = $this->getJson('/api/daily-motivation/today')->assertOk();
        $this->assertContains($response->json('motivation'), [
            'Kualitas adalah tanggung jawab bersama.',
            'Detail kecil, dampak besar.',
        ]);
        $this->assertNotNull($response->json('author'));
        $this->assertSame(2, $response->json('quote_total'));
    }

    public function test_daily_motivation_rejects_too_short_quote_text(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson('/api/admin/qa-settings', [
            'qa_lock_mode' => 'grace_then_lock',
            'qa_lock_minutes' => 0,
            'daily_motivation_enabled' => true,
            'daily_motivations' => ['ab'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('daily_motivations.0');
    }

    public function test_daily_motivation_can_be_disabled_by_an_admin(): void
    {
        AutomatedReminderSetting::current()->update(['daily_motivation_enabled' => false]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/daily-motivation/today')
            ->assertOk()
            ->assertJsonPath('motivation', null);
    }
}
