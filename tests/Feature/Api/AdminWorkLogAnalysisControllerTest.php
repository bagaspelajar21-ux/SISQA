<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class AdminWorkLogAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_analysis_is_requested_without_authentication(): void
    {
        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2026-10-01&end_date=2026-10-31')
            ->assertUnauthorized();
    }

    public function test_returns_403_when_a_user_requests_work_log_analysis(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2026-10-01&end_date=2026-10-31')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ditolak. Fitur ini hanya dapat diakses oleh Administrator.');
    }

    public function test_returns_403_when_a_user_requests_ai_work_log_analysis(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'user']));

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])->assertForbidden();
    }

    public function test_returns_401_when_ai_work_log_analysis_is_requested_without_authentication(): void
    {
        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])->assertUnauthorized();
    }

    public function test_summarizes_work_logs_by_date_and_user_with_optional_user_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstUser = User::factory()->create(['name' => 'Andi']);
        $secondUser = User::factory()->create(['name' => 'Budi']);
        $firstUser->workLogs()->createMany([
            ['work_date' => '2026-10-10', 'description' => 'Menguji halaman login'],
            ['work_date' => '2026-10-11', 'description' => 'Menguji halaman profil'],
        ]);
        $secondUser->workLogs()->createMany([
            ['work_date' => '2026-10-10', 'description' => 'Memeriksa bug laporan'],
            ['work_date' => '2026-11-01', 'description' => 'Pekerjaan di luar periode'],
        ]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2026-10-10&end_date=2026-10-11')
            ->assertOk()
            ->assertJsonPath('summary.total_entries', 3)
            ->assertJsonPath('summary.active_days', 2)
            ->assertJsonPath('summary.contributors', 2)
            ->assertJsonPath('summary.average_entries_per_active_day', 1.5)
            ->assertJsonPath('daily_activity.0.date', '2026-10-10')
            ->assertJsonPath('daily_activity.0.entry_count', 2)
            ->assertJsonPath('daily_activity.1.entry_count', 1)
            ->assertJsonPath('users.0.name', 'Andi')
            ->assertJsonPath('users.0.entry_count', 2)
            ->assertJsonCount(3, 'work_logs')
            ->assertJsonMissing(['description' => 'Pekerjaan di luar periode']);

        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2026-10-10&end_date=2026-10-11&user_id='.$secondUser->id)
            ->assertOk()
            ->assertJsonPath('summary.total_entries', 1)
            ->assertJsonPath('summary.contributors', 1)
            ->assertJsonPath('work_logs.0.user_name', 'Budi');
    }

    public function test_returns_422_when_analysis_date_range_is_invalid_or_exceeds_366_days(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2026-10-11&end_date=2026-10-10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->getJson('/api/admin/work-log-analysis/summary?start_date=2025-01-01&end_date=2026-01-02')
            ->assertUnprocessable()
            ->assertJsonPath('errors.end_date.0', 'Rentang analisis maksimal 366 hari.');
    }

    public function test_exports_filtered_work_log_analysis_to_pdf_and_excel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'QA Tester']);
        $user->workLogs()->create([
            'work_date' => '2026-10-10',
            'description' => 'Menguji modul pembayaran',
        ]);
        $user->workLogs()->create([
            'work_date' => '2026-10-10',
            'description' => '=HYPERLINK("https://example.test","Klik")',
        ]);
        Sanctum::actingAs($admin);
        $query = '?start_date=2026-10-10&end_date=2026-10-10&user_id='.$user->id;

        $pdfResponse = $this->get('/api/admin/work-log-analysis/export/pdf'.$query)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');
        $this->assertStringContainsString('%PDF', $pdfResponse->getContent());

        $excelResponse = $this->get('/api/admin/work-log-analysis/export/excel'.$query)->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $excelResponse->headers->get('content-type'),
        );
        $temporaryPath = tempnam(sys_get_temp_dir(), 'work-analysis-');

        try {
            file_put_contents($temporaryPath, $excelResponse->streamedContent());
            $spreadsheet = IOFactory::load($temporaryPath);

            $this->assertSame('Ringkasan', $spreadsheet->getSheet(0)->getTitle());
            $this->assertSame('Per Pengguna', $spreadsheet->getSheet(1)->getTitle());
            $this->assertSame('Detail Pekerjaan', $spreadsheet->getSheet(2)->getTitle());
            $this->assertSame('Menguji modul pembayaran', $spreadsheet->getSheet(2)->getCell('C2')->getValue());
            $this->assertSame('=HYPERLINK("https://example.test","Klik")', $spreadsheet->getSheet(2)->getCell('C3')->getValue());
            $this->assertSame('s', $spreadsheet->getSheet(2)->getCell('C3')->getDataType());
            $spreadsheet->disconnectWorksheets();
        } finally {
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    public function test_analyzes_filtered_work_logs_without_sending_user_names_to_openai(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Nama Rahasia']);
        $user->workLogs()->createMany([
            ['work_date' => '2026-10-10', 'description' => 'Menguji modul pembayaran'],
            ['work_date' => '2026-11-01', 'description' => 'Catatan di luar periode'],
        ]);
        Sanctum::actingAs($admin);
        config()->set('services.openai.key', 'test-openai-key');
        config()->set('services.openai.model', 'gpt-4o-mini');
        Http::preventStrayRequests();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Ringkasan pekerjaan QA.']],
                ],
            ]),
        ]);

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
        ])
            ->assertOk()
            ->assertJsonPath('analysis', 'Ringkasan pekerjaan QA.');

        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && str_contains($request->data()['messages'][1]['content'], 'Menguji modul pembayaran')
            && ! str_contains($request->data()['messages'][1]['content'], 'Nama Rahasia')
            && ! str_contains($request->data()['messages'][1]['content'], '"user_id"')
            && ! str_contains($request->data()['messages'][1]['content'], 'Catatan di luar periode'));
    }

    public function test_returns_503_when_openai_is_not_configured(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $user->workLogs()->create([
            'work_date' => '2026-10-10',
            'description' => 'Menguji fitur QA',
        ]);
        Sanctum::actingAs($admin);
        config()->set('services.openai.key', null);
        Http::preventStrayRequests();

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
        ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Fitur AI belum dikonfigurasi. Admin perlu mengatur OPENAI_API_KEY di server.');
    }

    public function test_returns_422_without_calling_openai_when_more_than_100_logs_are_selected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $user->workLogs()->createMany(array_fill(0, 101, [
            'work_date' => '2026-10-10',
            'description' => 'Menguji fitur QA',
        ]));
        Sanctum::actingAs($admin);
        config()->set('services.openai.key', 'test-openai-key');
        Http::preventStrayRequests();

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Analisis AI dibatasi maksimal 100 catatan. Persempit periode atau pilih satu pengguna.');
    }

    public function test_returns_502_when_openai_rejects_the_analysis_request(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $user->workLogs()->create([
            'work_date' => '2026-10-10',
            'description' => 'Menguji fitur QA',
        ]);
        Sanctum::actingAs($admin);
        config()->set('services.openai.key', 'test-openai-key');
        Http::preventStrayRequests();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(['error' => 'Unavailable'], 500),
        ]);

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Layanan AI gagal memproses analisis. Silakan coba kembali beberapa saat lagi.');
    }

    public function test_returns_502_when_openai_cannot_be_reached(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $user->workLogs()->create([
            'work_date' => '2026-10-10',
            'description' => 'Menguji fitur QA',
        ]);
        Sanctum::actingAs($admin);
        config()->set('services.openai.key', 'test-openai-key');
        Http::preventStrayRequests();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::failedConnection(),
        ]);

        $this->postJson('/api/admin/work-log-analysis/ai', [
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-10',
        ])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Layanan AI tidak dapat dihubungi. Silakan coba kembali beberapa saat lagi.');
    }
}
