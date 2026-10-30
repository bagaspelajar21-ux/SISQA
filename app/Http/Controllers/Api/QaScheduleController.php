<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\QaSchedule;
use App\Models\TestCase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class QaScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QaSchedule::with(['application', 'module', 'menu', 'tester', 'testCases']);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($testerId = $request->input('tester_id')) {
            $query->where('tester_id', $testerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('schedule_code', 'like', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('start_time', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('end_time', '<=', $endDate);
        }

        $schedules = $query->orderBy('start_time', 'desc')
            ->paginate($request->input('per_page', 15));

        // Append computed progress & auto-detect overdue/expired schedule time
        $schedules->getCollection()->transform(function ($item) {
            $isExpired = $item->end_time && now()->gt($item->end_time);
            if ($isExpired && !in_array($item->status, ['Completed', 'Cancelled', 'Overdue'])) {
                $item->update(['status' => 'Overdue']);
                $item->status = 'Overdue';
            }
            $item->progress_info = $item->progress;
            $item->is_expired = $isExpired;
            $item->can_continue_qa = !$isExpired && !in_array($item->status, ['Completed', 'Cancelled', 'Overdue']);
            return $item;
        });

        return response()->json($schedules);
    }

    public function calendar(Request $request): JsonResponse
    {
        $query = QaSchedule::with(['application', 'tester']);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($testerId = $request->input('tester_id')) {
            $query->where('tester_id', $testerId);
        }

        if ($request->input('start')) {
            $query->where('end_time', '>=', $request->input('start'));
        }

        if ($request->input('end')) {
            $query->where('start_time', '<=', $request->input('end'));
        }

        $events = $query->get()->map(function ($sch) {
            $color = match ($sch->status) {
                'Completed' => '#10B981',
                'In Progress' => '#3B82F6',
                'Overdue' => '#EF4444',
                'Cancelled' => '#9CA3AF',
                default => '#6366F1',
            };

            return [
                'id' => $sch->id,
                'title' => "[{$sch->schedule_code}] {$sch->title}",
                'start' => $sch->start_time->toISOString(),
                'end' => $sch->end_time->toISOString(),
                'status' => $sch->status,
                'priority' => $sch->priority,
                'tester' => $sch->tester?->name ?? 'Belum Ditugaskan',
                'application' => $sch->application?->name ?? '-',
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'progress' => $sch->progress,
                    'schedule_code' => $sch->schedule_code,
                    'testing_type' => $sch->testing_type,
                ],
            ];
        });

        return response()->json($events);
    }

    public function checkConflict(Request $request): JsonResponse
    {
        $testerId = $request->input('tester_id');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $excludeId = $request->input('exclude_id');

        if (!$testerId || !$startTime || !$endTime) {
            return response()->json(['has_conflict' => false]);
        }

        $conflicts = QaSchedule::where('tester_id', $testerId)
            ->whereNotIn('status', ['Completed', 'Cancelled'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($sub) use ($startTime, $endTime) {
                      $sub->where('start_time', '<=', $startTime)
                          ->where('end_time', '>=', $endTime);
                  });
            })
            ->get(['id', 'schedule_code', 'title', 'start_time', 'end_time']);

        return response()->json([
            'has_conflict' => $conflicts->isNotEmpty(),
            'conflicts' => $conflicts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'testing_type' => ['required', Rule::in(['Daily', 'Weekly', 'Regression', 'Retesting', 'Release', 'Smoke Testing'])],
            'tester_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'target_test_cases_count' => 'nullable|integer|min:0',
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'notes' => 'nullable|string',
            'status' => ['nullable', Rule::in(['Scheduled', 'In Progress', 'Completed', 'Overdue', 'Cancelled'])],
            'test_case_ids' => 'nullable|array',
            'test_case_ids.*' => 'exists:test_cases,id',
        ]);

        // Auto-generate code
        $yearMonth = date('Ym');
        $count = QaSchedule::where('schedule_code', 'like', "SCH-{$yearMonth}-%")->count();
        $validated['schedule_code'] = sprintf("SCH-%s-%03d", $yearMonth, $count + 1);
        $validated['status'] = $validated['status'] ?? 'Scheduled';

        $testCaseIds = $validated['test_case_ids'] ?? [];
        unset($validated['test_case_ids']);

        if (empty($validated['target_test_cases_count'])) {
            $validated['target_test_cases_count'] = count($testCaseIds);
        }

        $schedule = QaSchedule::create($validated);

        if (!empty($testCaseIds)) {
            $schedule->testCases()->sync($testCaseIds);
        }

        ActivityLog::record(
            'schedule_created',
            'Jadwal QA',
            "Admin membuat jadwal QA: {$schedule->title} ({$schedule->schedule_code})",
            'QaSchedule',
            $schedule->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil dibuat.',
            'schedule' => $schedule->load(['application', 'tester', 'testCases']),
        ], 201);
    }

    public function show(QaSchedule $schedule): JsonResponse
    {
        $isExpired = $schedule->end_time && now()->gt($schedule->end_time);
        if ($isExpired && !in_array($schedule->status, ['Completed', 'Cancelled', 'Overdue'])) {
            $schedule->update(['status' => 'Overdue']);
            $schedule->status = 'Overdue';
        }

        $schedule->load(['application', 'module', 'menu', 'flow', 'tester', 'testCases.latestExecution', 'testExecutions.tester', 'bugs']);
        $data = $schedule->toArray();
        $data['progress_info'] = $schedule->progress;
        $data['is_expired'] = $isExpired;
        $data['can_continue_qa'] = !$isExpired && !in_array($schedule->status, ['Completed', 'Cancelled', 'Overdue']);

        return response()->json(['schedule' => $data]);
    }

    public function update(Request $request, QaSchedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'application_flow_id' => 'nullable|exists:application_flows,id',
            'testing_type' => ['required', Rule::in(['Daily', 'Weekly', 'Regression', 'Retesting', 'Release', 'Smoke Testing'])],
            'tester_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'target_test_cases_count' => 'nullable|integer|min:0',
            'priority' => ['required', Rule::in(['Urgent', 'High', 'Medium', 'Low'])],
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['Scheduled', 'In Progress', 'Completed', 'Overdue', 'Cancelled'])],
            'test_case_ids' => 'nullable|array',
            'test_case_ids.*' => 'exists:test_cases,id',
        ]);

        $testCaseIds = $validated['test_case_ids'] ?? null;
        unset($validated['test_case_ids']);

        $old = $schedule->only(['title', 'status', 'tester_id', 'start_time', 'end_time', 'priority']);
        $schedule->update($validated);

        if ($testCaseIds !== null) {
            $schedule->testCases()->sync($testCaseIds);
        }

        ActivityLog::record(
            'schedule_updated',
            'Jadwal QA',
            "Jadwal QA {$schedule->schedule_code} diperbarui. Status: {$schedule->status}",
            'QaSchedule',
            $schedule->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil diperbarui.',
            'schedule' => $schedule->fresh()->load(['application', 'tester', 'testCases']),
        ]);
    }

    public function destroy(QaSchedule $schedule): JsonResponse
    {
        $code = $schedule->schedule_code;
        $schedule->delete();

        ActivityLog::record(
            'schedule_deleted',
            'Jadwal QA',
            "Admin menghapus jadwal QA: {$code}",
            'QaSchedule',
            $schedule->id,
            ['schedule_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Jadwal QA berhasil dihapus.',
        ]);
    }
}
