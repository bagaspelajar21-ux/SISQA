<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Bug;
use App\Models\BugHistory;
use App\Models\Retest;
use App\Models\RetestEvidence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RetestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Retest::with(['bug.application', 'testCase', 'tester', 'evidences']);

        if ($bugId = $request->input('bug_id')) {
            $query->where('bug_id', $bugId);
        }

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($testerId = $request->input('tester_id')) {
            $query->where('tester_id', $testerId);
        }

        $retests = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($retests);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'bug_id' => 'required|exists:bugs,id',
            'tested_version' => 'nullable|string|max:50',
            'expected_result' => 'nullable|string',
            'actual_result' => 'required|string',
            'status' => ['required', Rule::in(['Pending Retest', 'Passed', 'Failed', 'Reopened'])],
            'notes' => 'nullable|string',
            'evidences' => 'nullable|array',
            'evidences.*' => 'file|max:51200|mimes:png,jpg,jpeg,webp,pdf,mp4',
        ]);

        $bug = Bug::findOrFail($validated['bug_id']);

        $retest = Retest::create([
            'bug_id' => $bug->id,
            'test_case_id' => $bug->test_case_id,
            'qa_schedule_id' => $bug->qa_schedule_id,
            'application_id' => $bug->application_id,
            'module_id' => $bug->module_id,
            'menu_id' => $bug->menu_id,
            'tester_id' => $user->id,
            'retest_date' => now(),
            'tested_version' => $validated['tested_version'] ?? $bug->application?->version,
            'expected_result' => $validated['expected_result'] ?? $bug->expected_result,
            'actual_result' => $validated['actual_result'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Upload retest evidences
        if ($request->hasFile('evidences')) {
            foreach ($request->file('evidences') as $file) {
                $path = $file->store('evidences/retests', 'local');
                RetestEvidence::create([
                    'retest_id' => $retest->id,
                    'file_name' => basename($path),
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        // Update Bug Status according to retest outcome
        $oldBugStatus = $bug->status;
        $newBugStatus = null;
        if ($retest->status === 'Passed') {
            $newBugStatus = 'Closed';
        } elseif ($retest->status === 'Failed' || $retest->status === 'Reopened') {
            $newBugStatus = 'Reopened';
        }

        if ($newBugStatus && $newBugStatus !== $oldBugStatus) {
            $bug->update(['status' => $newBugStatus]);

            BugHistory::create([
                'bug_id' => $bug->id,
                'user_id' => $user->id,
                'field_changed' => 'status',
                'old_value' => $oldBugStatus,
                'new_value' => $newBugStatus,
                'notes' => "Status bug diperbarui otomatis dari hasil retesting: {$retest->status}.",
            ]);
        }

        ActivityLog::record(
            'retest_performed',
            'Retesting',
            "Tester {$user->name} melakukan retesting pada bug {$bug->bug_code}. Hasil: {$retest->status}",
            'Retest',
            $retest->id,
            ['bug_status' => $oldBugStatus],
            ['retest_status' => $retest->status, 'bug_status' => $bug->fresh()->status],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Hasil retesting berhasil dicatat.',
            'retest' => $retest->load(['bug', 'tester', 'evidences']),
        ], 201);
    }

    public function show(Retest $retest): JsonResponse
    {
        return response()->json([
            'retest' => $retest->load(['bug.application', 'testCase', 'tester', 'evidences.uploader']),
        ]);
    }
}
