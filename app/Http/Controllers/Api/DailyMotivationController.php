<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomatedReminderSetting;
use App\Services\IndonesianHolidayService;
use App\Support\DailyMotivationThemes;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class DailyMotivationController extends Controller
{
    public function today(IndonesianHolidayService $holidays): JsonResponse
    {
        $settings = AutomatedReminderSetting::current();
        $date = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        $holiday = $holidays->holidayFor($date);
        $occasion = $holiday['name'] ?? null;
        $theme = $occasion !== null
            ? DailyMotivationThemes::themeForHolidayName($occasion)
            : DailyMotivationThemes::GENERAL;

        $quotes = $settings->normalizedMotivations();
        $motivation = null;
        $author = null;
        $quoteIndex = null;
        $quoteTotal = 0;

        if ($settings->daily_motivation_enabled) {
            $firstDay = CarbonImmutable::parse('2000-01-01', 'Asia/Jakarta');
            $dayIndex = (int) $firstDay->diffInDays($date);

            if ($occasion !== null) {
                // Hari libur: gunakan kutipan bertema hari besar terkait.
                $themedQuotes = DailyMotivationThemes::quotesFor($theme);
                $quoteTotal = count($themedQuotes);
                if ($quoteTotal > 0) {
                    $quoteIndex = $dayIndex % $quoteTotal;
                    $motivation = $themedQuotes[$quoteIndex]['text'];
                    $author = $themedQuotes[$quoteIndex]['author'];
                }
            }

            // Hari biasa (atau tema libur tanpa kutipan): rotasi kutipan admin.
            if ($motivation === null && count($quotes) > 0) {
                $quoteTotal = count($quotes);
                $quoteIndex = $dayIndex % $quoteTotal;
                $motivation = $quotes[$quoteIndex]['text'];
                $author = $quotes[$quoteIndex]['author'];
            }
        }

        return response()->json([
            'motivation' => $motivation,
            'author' => $author,
            'quote_index' => $quoteIndex,
            'quote_total' => $quoteTotal,
            'date' => $date->format('Y-m-d'),
            'timezone' => 'Asia/Jakarta',
            'occasion' => $occasion,
            'theme' => $theme,
            'image_url' => $settings->daily_motivation_image_path
                ? '/storage/'.ltrim($settings->daily_motivation_image_path, '/')
                : null,
        ]);
    }
}
