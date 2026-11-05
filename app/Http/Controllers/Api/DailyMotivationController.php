<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomatedReminderSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class DailyMotivationController extends Controller
{
    public function today(): JsonResponse
    {
        $settings = AutomatedReminderSetting::current();
        $date = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        $quotes = $settings->normalizedMotivations();
        $motivation = null;
        $author = null;
        $quoteIndex = null;

        if ($settings->daily_motivation_enabled && count($quotes) > 0) {
            $firstDay = CarbonImmutable::parse('2000-01-01', 'Asia/Jakarta');
            $dayIndex = (int) $firstDay->diffInDays($date);
            $quoteIndex = $dayIndex % count($quotes);
            $motivation = $quotes[$quoteIndex]['text'];
            $author = $quotes[$quoteIndex]['author'];
        }

        return response()->json([
            'motivation' => $motivation,
            'author' => $author,
            'quote_index' => $quoteIndex,
            'quote_total' => count($quotes),
            'date' => $date->format('Y-m-d'),
            'timezone' => 'Asia/Jakarta',
            'image_url' => $settings->daily_motivation_image_path
                ? '/storage/'.ltrim($settings->daily_motivation_image_path, '/')
                : null,
        ]);
    }
}
