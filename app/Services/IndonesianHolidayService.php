<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengambil kalender hari libur Indonesia (nasional, keagamaan, cuti bersama)
 * dari API publik api-harilibur, di-cache per tahun. Bila API tidak
 * tersedia, fallback ke daftar libur tetap yang dapat dihitung lokal.
 */
class IndonesianHolidayService
{
    private const API_URL = 'https://api-harilibur.vercel.app/api';

    private const CACHE_TTL_SECONDS = 21600; // 6 jam

    /**
     * Daftar hari libur untuk tanggal tertentu.
     *
     * @return array{date: string, name: string, is_national: bool}|null
     */
    public function holidayFor(CarbonImmutable $date): ?array
    {
        $holidays = $this->holidaysForYear($date->year);

        $dateString = $date->format('Y-m-d');
        foreach ($holidays as $holiday) {
            if (($holiday['date'] ?? null) === $dateString) {
                return $holiday;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{date: string, name: string, is_national: bool}>
     */
    private function holidaysForYear(int $year): array
    {
        return Cache::remember(
            "indonesian_holidays:{$year}",
            self::CACHE_TTL_SECONDS,
            function () use ($year): array {
                try {
                    $response = Http::timeout(5)->get(self::API_URL, ['year' => $year]);

                    if ($response->successful()) {
                        $holidays = collect($response->json() ?? [])
                            ->map(fn ($item) => [
                                'date' => $item['holiday_date'] ?? null,
                                'name' => $item['holiday_name'] ?? null,
                                'is_national' => (bool) ($item['is_national_holiday'] ?? true),
                            ])
                            ->filter(fn ($item) => $item['date'] && $item['name'])
                            ->values()
                            ->all();

                        if ($holidays !== []) {
                            return $holidays;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal memuat kalender hari libur Indonesia dari API.', [
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                }

                return $this->fallbackHolidays($year);
            }
        );
    }

    /**
     * Libur tanggal tetap yang bisa dihitung tanpa API (hari nasional Masehi
     * dan cuti bersama tidak dapat diprediksi — hanya libur tetap yang masuk).
     *
     * @return array<int, array{date: string, name: string, is_national: bool}>
     */
    private function fallbackHolidays(int $year): array
    {
        // Hanya libur ber-tanggal tetap — hari keagamaan yang tanggalnya
        // bergeser tiap tahun tidak dapat diprediksi tanpa API.
        $fixed = [
            '01-01' => 'Tahun Baru Masehi',
            '02-14' => 'Hari Kasih Sayang',
            '05-01' => 'Hari Buruh Internasional',
            '06-01' => 'Hari Lahir Pancasila',
            '08-17' => 'Hari Kemerdekaan RI',
            '12-25' => 'Hari Raya Natal',
        ];

        $holidays = [];
        foreach ($fixed as $md => $name) {
            $holidays[] = [
                'date' => sprintf('%04d-%s', $year, $md),
                'name' => $name,
                'is_national' => true,
            ];
        }

        return $holidays;
    }
}
