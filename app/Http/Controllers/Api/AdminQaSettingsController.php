<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomatedReminderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AdminQaSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'settings' => AutomatedReminderSetting::current()->only([
                'qa_lock_mode',
                'qa_lock_minutes',
                'daily_motivation_enabled',
                'daily_motivations',
                'daily_motivation_image_path',
            ]),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qa_lock_mode' => ['required', Rule::in(['grace_then_lock', 'lock_for_duration', 'follow_schedule'])],
            'qa_lock_minutes' => [
                'required',
                'integer',
                Rule::when(
                    $request->input('qa_lock_mode') === 'lock_for_duration',
                    ['min:1', 'max:1440'],
                    ['min:0', 'max:1440'],
                ),
            ],
            'daily_motivation_enabled' => ['required', 'boolean'],
            'daily_motivations' => ['required', 'array', 'min:1', 'max:100'],
            'daily_motivations.*' => ['required'],
        ]);
        $validated['daily_motivations'] = $this->normalizeMotivations($validated['daily_motivations']);
        if ($validated['qa_lock_mode'] === 'lock_for_duration' && $validated['qa_lock_minutes'] < 1) {
            return response()->json([
                'message' => 'Durasi penguncian otomatis harus minimal 1 menit.',
                'errors' => ['qa_lock_minutes' => ['Durasi penguncian otomatis harus minimal 1 menit.']],
            ], 422);
        }

        $settings = AutomatedReminderSetting::current();
        $settings->fill($validated)->save();

        return response()->json([
            'message' => 'Pengaturan QA dan motivasi harian berhasil disimpan.',
            'settings' => $settings->fresh()->only([
                'qa_lock_mode',
                'qa_lock_minutes',
                'daily_motivation_enabled',
                'daily_motivations',
                'daily_motivation_image_path',
            ]),
        ]);
    }

    public function uploadMotivationImage(Request $request): JsonResponse
    {
        if (! Schema::hasColumn('automated_reminder_settings', 'daily_motivation_image_path')) {
            return response()->json([
                'message' => 'Fitur gambar belum tersedia karena migrasi database belum diterapkan.',
            ], 503);
        }

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $settings = AutomatedReminderSetting::current();
        $newPath = $validated['image']->storePublicly('daily-motivation', 'public');

        if ($newPath === false) {
            throw new RuntimeException('Desain motivasi gagal disimpan.');
        }

        $oldPath = $settings->daily_motivation_image_path;
        $settings->daily_motivation_image_path = $newPath;
        $settings->save();

        if ($oldPath !== null) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'message' => 'Desain motivasi berhasil diunggah.',
            'image_url' => '/storage/'.ltrim($newPath, '/'),
        ]);
    }

    public function deleteMotivationImage(): JsonResponse
    {
        if (! Schema::hasColumn('automated_reminder_settings', 'daily_motivation_image_path')) {
            return response()->json([
                'message' => 'Fitur gambar belum tersedia karena migrasi database belum diterapkan.',
            ], 503);
        }

        $settings = AutomatedReminderSetting::current();
        $imagePath = $settings->daily_motivation_image_path;
        $settings->daily_motivation_image_path = null;
        $settings->save();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return response()->json([
            'message' => 'Desain motivasi berhasil dihapus.',
        ]);
    }

    /**
     * Normalisasi entri motivasi menjadi {text, author?}. Menerima string
     * polos, string dengan sufiks " | Penulis", atau objek {text, author}.
     *
     * @param  array<int, mixed>  $entries
     * @return array<int, array{text: string, author: ?string}>
     */
    private function normalizeMotivations(array $entries): array
    {
        $normalized = [];
        $errors = [];

        foreach ($entries as $index => $entry) {
            $text = null;
            $author = null;

            if (is_string($entry)) {
                $entry = trim($entry);
                // Format "kutipan | Penulis" — pisahkan pada pemisah '|' terakhir.
                if (str_contains($entry, '|')) {
                    [$text, $author] = array_map('trim', explode('|', $entry, 2) + [1 => '']);
                    $author = $author !== '' ? mb_substr($author, 0, 120) : null;
                } else {
                    $text = $entry;
                }
            } elseif (is_array($entry)) {
                $text = trim((string) ($entry['text'] ?? ''));
                $author = isset($entry['author']) ? trim((string) $entry['author']) : null;
                $author = $author !== '' ? mb_substr($author, 0, 120) : null;
            }

            $text = is_string($text) ? trim($text) : '';
            if (mb_strlen($text) < 3 || mb_strlen($text) > 500) {
                $errors["daily_motivations.{$index}"] = ['Teks motivasi harus 3-500 karakter.'];

                continue;
            }

            $normalized[] = ['text' => $text, 'author' => $author];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }
}
