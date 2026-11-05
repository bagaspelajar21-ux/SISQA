<?php

namespace Tests\Feature;

use App\Models\AutomatedReminderSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DailyMotivationHolidayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AutomatedReminderSetting::current();
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_menampilkan_kutipan_tema_nasional_saat_hari_libur(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-17 09:00', 'Asia/Jakarta'));

        Http::fake([
            'api-harilibur.vercel.app/*' => Http::response([
                ['holiday_date' => '2026-08-17', 'holiday_name' => 'Hari Kemerdekaan RI', 'is_national_holiday' => true],
            ]),
        ]);

        $response = $this->getJson('/api/daily-motivation/today');

        $response->assertOk()
            ->assertJsonPath('occasion', 'Hari Kemerdekaan RI')
            ->assertJsonPath('theme', 'nasional');

        $this->assertNotNull($response->json('motivation'));
        $this->assertSame(6, $response->json('quote_total')); // jumlah kutipan tema nasional
    }

    public function test_menampilkan_kutipan_tema_islam_saat_idul_fitri(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-20 09:00', 'Asia/Jakarta'));

        Http::fake([
            'api-harilibur.vercel.app/*' => Http::response([
                ['holiday_date' => '2026-03-20', 'holiday_name' => 'Idul Fitri 1447 H', 'is_national_holiday' => true],
            ]),
        ]);

        $response = $this->getJson('/api/daily-motivation/today');

        $response->assertOk()
            ->assertJsonPath('occasion', 'Idul Fitri 1447 H')
            ->assertJsonPath('theme', 'islam');

        $this->assertNotNull($response->json('motivation'));
    }

    public function test_hari_biasa_tetap_memakai_kutipan_admin(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-18 09:00', 'Asia/Jakarta'));

        Http::fake([
            'api-harilibur.vercel.app/*' => Http::response([
                ['holiday_date' => '2026-08-17', 'holiday_name' => 'Hari Kemerdekaan RI', 'is_national_holiday' => true],
            ]),
        ]);

        $response = $this->getJson('/api/daily-motivation/today');

        $response->assertOk()
            ->assertJsonPath('occasion', null)
            ->assertJsonPath('theme', 'umum');

        $this->assertNotNull($response->json('motivation'));
        $this->assertSame(7, $response->json('quote_total')); // jumlah default_motivations admin
    }

    public function test_fallback_libur_tetap_bila_api_gagal(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-12-25 09:00', 'Asia/Jakarta'));

        Http::fake([
            'api-harilibur.vercel.app/*' => Http::response('Server Error', 500),
        ]);

        $response = $this->getJson('/api/daily-motivation/today');

        $response->assertOk()
            ->assertJsonPath('occasion', 'Hari Raya Natal')
            ->assertJsonPath('theme', 'kristen');
    }
}
