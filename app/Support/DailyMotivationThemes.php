<?php

namespace App\Support;

/**
 * Tema motivasi harian berdasarkan hari besar Indonesia.
 * Setiap tema punya kumpulan kutipan {text, author} yang berotasi
 * berdasarkan index hari dalam tahun.
 */
class DailyMotivationThemes
{
    public const NATIONAL = 'nasional';

    public const ISLAM = 'islam';

    public const CHRISTIAN = 'kristen';

    public const HINDU = 'hindu';

    public const BUDDHIST = 'buddha';

    public const GENERAL = 'umum';

    /**
     * Kunci kata pada nama hari libur → tema.
     * Urutan diperiksa berurutan; keyword pertama yang cocok menang.
     *
     * @var array<int, array{theme: string, keywords: array<int, string>}>
     */
    private const HOLIDAY_THEME_RULES = [
        ['theme' => self::ISLAM, 'keywords' => [
            'idul fitri', 'lebaran', 'idul adha', 'qurban', 'maulid', 'maulidur',
            'isra mikraj', 'tahun baru islam', '1 muharram', 'nuzulul', 'isra\' miraj',
        ]],
        ['theme' => self::CHRISTIAN, 'keywords' => [
            'kenaikan isa', 'wafat isa', 'paskah', 'pentakosta', 'pentakoses',
            'kebangkitan', 'advent', 'natal',
        ]],
        ['theme' => self::HINDU, 'keywords' => [
            'nyepi', 'saka', 'galungan', 'kuningan', 'saraswati',
        ]],
        ['theme' => self::BUDDHIST, 'keywords' => [
            'waisak', 'wesak', 'katina', 'asadha',
        ]],
        ['theme' => self::NATIONAL, 'keywords' => [
            'tahun baru masehi', 'kemerdekaan', 'pahlawan', 'buruh', 'pancasila',
            'kartini', 'pendidikan', 'ibu', 'batik', 'pahlawan nasional',
        ]],
    ];

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    public static function quotesFor(string $theme): array
    {
        return match ($theme) {
            self::ISLAM => self::islamQuotes(),
            self::CHRISTIAN => self::christianQuotes(),
            self::HINDU => self::hinduQuotes(),
            self::BUDDHIST => self::buddhistQuotes(),
            self::NATIONAL => self::nationalQuotes(),
            default => [],
        };
    }

    /**
     * Deteksi tema dari nama hari libur (case-insensitive).
     */
    public static function themeForHolidayName(string $holidayName): string
    {
        $name = mb_strtolower($holidayName);

        foreach (self::HOLIDAY_THEME_RULES as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($name, mb_strtolower(trim($keyword)))) {
                    return $rule['theme'];
                }
            }
        }

        // Libur nasional/cuti bersama yang tidak masuk kategori keagamaan
        // tetap dianggap tema nasional.
        return self::NATIONAL;
    }

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    private static function islamQuotes(): array
    {
        return [
            ['text' => 'Semoga hari raya ini membawa keberkahan, kedamaian, dan kebersamaan bagi kita semua.', 'author' => 'SISQA'],
            ['text' => 'Di hari yang suci ini, mari tingkatkan ketekunan dan kebaikan dalam setiap amal.', 'author' => 'SISQA'],
            ['text' => 'Kebersihan hati dan kerja yang ikhlas adalah kunci keberhasilan dunia dan akhirat.', 'author' => 'SISQA'],
            ['text' => 'Jadilah yang terbaik dalam pekerjaanmu, karena kualitas adalah sebagian dari iman.', 'author' => 'SISQA'],
            ['text' => 'Semangat menjaga kualitas perangkat lunak juga adalah bentuk menjaga amanah.', 'author' => 'SISQA'],
            ['text' => 'Hari istimewa untuk introspeksi, bersyukur, dan melanjutkan perjuangan dengan semangat baru.', 'author' => 'SISQA'],
            ['text' => 'Setiap kebaikan kecil yang konsisten akan membawa hasil yang besar.', 'author' => 'SISQA'],
        ];
    }

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    private static function christianQuotes(): array
    {
        return [
            ['text' => 'Selamat hari raya! Semoga damai dan sukacita memenuhi hari-harimu.', 'author' => 'SISQA'],
            ['text' => 'Kasih, pengharapan, dan semangat—tetaplah menjadi teladan dalam tim QA.', 'author' => 'SISQA'],
            ['text' => 'Hari yang diberkati adalah hari untuk melakukan yang terbaik dalam pekerjaanmu.', 'author' => 'SISQA'],
            ['text' => 'Semoga hari raya ini mengingatkan kita untuk selalu berbuat baik dan bekerja dengan tulus.', 'author' => 'SISQA'],
        ];
    }

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    private static function hinduQuotes(): array
    {
        return [
            ['text' => 'Selamat hari raya Nyepi! Semoga pikiran jernih membawa hasil pengujian yang cemerlang.', 'author' => 'SISQA'],
            ['text' => 'Dalam ketenangan ada kekuatan—manfaatkan untuk fokus dan ketelitian ekstra.', 'author' => 'SISQA'],
            ['text' => 'Semoga hari suci ini memberikan kedamaian dan semangat baru bagi tim.', 'author' => 'SISQA'],
            ['text' => 'Bersyukur, introspeksi, dan berbenah—lanjutkan dengan semangat yang lebih baik.', 'author' => 'SISQA'],
        ];
    }

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    private static function buddhistQuotes(): array
    {
        return [
            ['text' => 'Selamat Hari Raya Waisak! Semoga kebijaksanaan dan welas asih selalu menyertai kita.', 'author' => 'SISQA'],
            ['text' => 'Semangat untuk berbuat baik dan bekerja dengan penuh kesadaran.', 'author' => 'SISQA'],
            ['text' => 'Hari suci untuk merenung dan melanjutkan perjalanan dengan hati yang tenang.', 'author' => 'SISQA'],
            ['text' => 'Kualitas terbaik lahir dari pikiran yang jernih dan niat yang tulus.', 'author' => 'SISQA'],
        ];
    }

    /**
     * @return array<int, array{text: string, author: ?string}>
     */
    private static function nationalQuotes(): array
    {
        return [
            ['text' => 'Dirgahayu Republik Indonesia! Semangat membangun kualitas digital tanah air.', 'author' => 'SISQA'],
            ['text' => 'Bersatu dan berkarya—setiap pengujian yang teliti adalah kontribusi nyata bagi bangsa.', 'author' => 'SISQA'],
            ['text' => 'Hari besar nasional adalah momentum untuk berkarya lebih baik dan berinovasi.', 'author' => 'SISQA'],
            ['text' => 'Mari wujudkan Indonesia yang unggul melalui kerja keras dan kualitas terbaik.', 'author' => 'SISQA'],
            ['text' => 'Semangat persatuan dan kerja sama tim adalah kunci kesuksesan QA.', 'author' => 'SISQA'],
            ['text' => 'Hari peringatan nasional: saatnya mengingat jasa pahlawan dan melanjutkan perjuangan.', 'author' => 'SISQA'],
        ];
    }
}
