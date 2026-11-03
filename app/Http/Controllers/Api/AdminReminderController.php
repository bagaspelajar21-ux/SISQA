<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminReminder;
use App\Models\AutomatedReminderSetting;
use App\Models\User;
use App\Services\ReminderDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminReminderController extends Controller
{
    public function automatedSettings(): JsonResponse
    {
        return response()->json([
            'settings' => AutomatedReminderSetting::current(),
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function updateAutomatedSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'qa_enabled' => ['required', 'boolean'],
            'qa_time' => ['required', 'date_format:H:i'],
            'daily_report_enabled' => ['required', 'boolean'],
            'daily_report_time' => ['required', 'date_format:H:i'],
            'weekly_report_enabled' => ['required', 'boolean'],
            'weekly_report_time' => ['required', 'date_format:H:i'],
            'monthly_report_enabled' => ['required', 'boolean'],
            'monthly_report_time' => ['required', 'date_format:H:i'],
            'motivation_enabled' => ['required', 'boolean'],
            'motivation_message' => ['required', 'string', 'max:500'],
        ]);

        $settings = AutomatedReminderSetting::current();
        $settings->fill($validated)->save();

        return response()->json([
            'message' => 'Pengaturan pengingat otomatis berhasil disimpan.',
            'settings' => $settings->fresh(),
            'timezone' => 'Asia/Jakarta',
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'reminders' => AdminReminder::query()
                ->with(['creator:id,name', 'recipientUser:id,name'])
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function recipients(): JsonResponse
    {
        return response()->json([
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
        ]);
    }

    public function store(Request $request, ReminderDeliveryService $delivery): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:5000'],
            'recipient_type' => ['required', Rule::in(['all', 'role', 'user'])],
            'recipient_role' => [
                Rule::requiredIf($request->input('recipient_type') === 'role'),
                'nullable',
                Rule::in(['admin', 'user', 'owner']),
            ],
            'recipient_user_id' => [
                Rule::requiredIf($request->input('recipient_type') === 'user'),
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
            'scheduled_at' => ['required', 'date'],
        ]);

        $reminder = AdminReminder::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $isDue = $reminder->scheduled_at->lessThanOrEqualTo(now());

        if ($isDue) {
            $delivery->dispatchAdminReminder($reminder->id);
            $reminder->refresh();
        }

        return response()->json([
            'message' => $reminder->delivered_at
                ? 'Pengingat berhasil dikirim.'
                : ($isDue
                    ? 'Belum ada penerima aktif. Pengingat tetap tersimpan dan akan dicoba kembali.'
                    : 'Pengingat berhasil dijadwalkan.'),
            'reminder' => $reminder->load(['creator:id,name', 'recipientUser:id,name']),
        ], 201);
    }

    public function destroy(AdminReminder $reminder): JsonResponse
    {
        if ($reminder->delivered_at !== null) {
            return response()->json([
                'message' => 'Pengingat yang sudah dikirim tidak dapat dibatalkan.',
            ], 409);
        }

        $reminder->delete();

        return response()->json([
            'message' => 'Pengingat terjadwal berhasil dibatalkan.',
        ]);
    }
}
