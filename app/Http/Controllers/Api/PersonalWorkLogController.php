<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PersonalWorkLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        [$weekStart, $weekEnd] = $this->weekRange($request);
        $workLogs = $request->user()->workLogs()
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'week' => [
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
            ],
            'work_logs' => $workLogs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatedWorkLog($request);
        $workLog = $request->user()->workLogs()->create($validated);

        return response()->json(['work_log' => $workLog], 201);
    }

    public function update(Request $request, WorkLog $workLog): JsonResponse
    {
        $ownedWorkLog = $request->user()->workLogs()->findOrFail($workLog->id);
        $ownedWorkLog->update($this->validatedWorkLog($request));

        return response()->json(['work_log' => $ownedWorkLog->refresh()]);
    }

    public function destroy(Request $request, WorkLog $workLog): Response
    {
        $ownedWorkLog = $request->user()->workLogs()->findOrFail($workLog->id);
        $ownedWorkLog->delete();

        return response()->noContent();
    }

    public function exportPdf(Request $request): Response
    {
        [$weekStart, $weekEnd] = $this->weekRange($request);
        $workLogsByDate = $request->user()->workLogs()
            ->whereBetween('work_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('work_date')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (WorkLog $workLog): string => $workLog->work_date->toDateString());

        $weekDays = collect(range(0, 6))->map(function (int $offset) use ($weekStart, $workLogsByDate): array {
            $date = $weekStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'work_logs' => $workLogsByDate->get($date->toDateString(), collect()),
            ];
        });

        $fileName = sprintf(
            'Laporan_Pekerjaan_%s_sampai_%s.pdf',
            $weekStart->toDateString(),
            $weekEnd->toDateString()
        );

        return Pdf::loadView('reports.personal_work_logs', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekDays' => $weekDays,
            'user' => $request->user(),
        ])->setPaper('a4')->download($fileName);
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function weekRange(Request $request): array
    {
        $validated = $request->validate([
            'week_of' => ['required', 'date_format:Y-m-d'],
        ]);

        $weekStart = Carbon::createFromFormat('Y-m-d', $validated['week_of'])->startOfWeek(Carbon::MONDAY);

        return [$weekStart, $weekStart->copy()->endOfWeek(Carbon::SUNDAY)];
    }

    /**
     * @return array{work_date: string, description: string}
     */
    private function validatedWorkLog(Request $request): array
    {
        $validated = $request->validate([
            'week_of' => ['required', 'date_format:Y-m-d'],
            'work_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['required', 'string', 'max:1000'],
        ], [
            'description.required' => 'Uraian pekerjaan wajib diisi.',
        ]);

        $workDate = Carbon::createFromFormat('Y-m-d', $validated['work_date']);
        $weekStart = Carbon::createFromFormat('Y-m-d', $validated['week_of'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        if ($workDate->lt($weekStart) || $workDate->gt($weekEnd)) {
            throw ValidationException::withMessages([
                'work_date' => 'Tanggal pekerjaan harus berada dalam minggu yang dipilih.',
            ]);
        }

        $validated['description'] = trim($validated['description']);
        unset($validated['week_of']);

        return $validated;
    }
}
