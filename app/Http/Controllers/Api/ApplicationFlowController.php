<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApplicationFlow;
use App\Models\ApplicationFlowFile;
use App\Models\ApplicationFlowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationFlowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = ApplicationFlow::with(['application', 'module', 'menu', 'creator', 'files']);

        // Non-admin can only see Published flows unless viewing assigned items
        if ($user->role !== 'admin') {
            $query->where('status', 'Published');
        } elseif ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('flow_code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $flows = $query->withCount(['steps', 'files', 'testCases', 'bugs'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($flows);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // Allow steps to be passed as JSON string when using multipart/form-data
        if (is_string($request->input('steps'))) {
            $decoded = json_decode($request->input('steps'), true);
            $request->merge(['steps' => is_array($decoded) ? $decoded : []]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'description' => 'nullable|string',
            'process_goal' => 'nullable|string',
            'actors' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'expected_outcome' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'steps' => 'nullable|array',
            'steps.*.step_number' => 'required_with:steps|integer',
            'steps.*.title' => 'required_with:steps|string',
            'steps.*.step_type' => ['required_with:steps', Rule::in(['Start', 'Process', 'Decision', 'End'])],
            'steps.*.activity_description' => 'nullable|string',
            'steps.*.actor' => 'nullable|string',
            'steps.*.input_data' => 'nullable|string',
            'steps.*.expected_result' => 'nullable|string',
            'steps.*.decision_branches' => 'nullable|array',
            'steps.*.notes' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:102400',
        ]);

        // Auto-generate unique flow code
        $prefix = 'FLOW';
        $lastId = (int) ApplicationFlow::max('id') + 1;
        $validated['flow_code'] = sprintf("%s-%04d", $prefix, $lastId);
        while (ApplicationFlow::where('flow_code', $validated['flow_code'])->exists()) {
            $lastId++;
            $validated['flow_code'] = sprintf("%s-%04d", $prefix, $lastId);
        }

        $validated['created_by'] = $user->id;
        $validated['version'] = $validated['version'] ?? '1.0';

        $stepsData = $validated['steps'] ?? [];
        unset($validated['steps'], $validated['files']);

        $flow = ApplicationFlow::create($validated);

        if (!empty($stepsData)) {
            foreach ($stepsData as $s) {
                if (!empty(trim($s['title'] ?? ''))) {
                    $s['flow_id'] = $flow->id;
                    ApplicationFlowStep::create($s);
                }
            }
        }

        // Save uploaded files if provided during creation
        if ($request->hasFile('files')) {
            $this->saveFlowFiles($request->file('files'), $flow, $user->id, $request->input('file_description'));
        }

        ActivityLog::record(
            'flow_created',
            'Flow Aplikasi',
            "Admin membuat flow aplikasi: {$flow->title} ({$flow->flow_code}) [Status: {$flow->status}]",
            'ApplicationFlow',
            $flow->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Flow aplikasi berhasil dibuat.',
            'flow' => $flow->load(['application', 'module', 'menu', 'steps', 'files.uploader', 'creator']),
        ], 201);
    }

    public function show(ApplicationFlow $flow): JsonResponse
    {
        return response()->json([
            'flow' => $flow->load([
                'application',
                'module',
                'menu',
                'steps',
                'files.uploader',
                'creator',
                'updater',
                'testCases',
                'bugs',
            ]),
        ]);
    }

    public function update(Request $request, ApplicationFlow $flow): JsonResponse
    {
        $user = $request->user();

        if (is_string($request->input('steps'))) {
            $decoded = json_decode($request->input('steps'), true);
            $request->merge(['steps' => is_array($decoded) ? $decoded : []]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'nullable|exists:application_modules,id',
            'menu_id' => 'nullable|exists:application_menus,id',
            'description' => 'nullable|string',
            'process_goal' => 'nullable|string',
            'actors' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'expected_outcome' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'steps' => 'nullable|array',
            'steps.*.step_number' => 'required_with:steps|integer',
            'steps.*.title' => 'required_with:steps|string',
            'steps.*.step_type' => ['required_with:steps', Rule::in(['Start', 'Process', 'Decision', 'End'])],
            'steps.*.activity_description' => 'nullable|string',
            'steps.*.actor' => 'nullable|string',
            'steps.*.input_data' => 'nullable|string',
            'steps.*.expected_result' => 'nullable|string',
            'steps.*.decision_branches' => 'nullable|array',
            'steps.*.notes' => 'nullable|string',
            'files' => 'nullable|array',
            'files.*' => 'file|max:102400',
        ]);

        $stepsData = $validated['steps'] ?? null;
        unset($validated['steps'], $validated['files']);

        $validated['updated_by'] = $user->id;
        $old = $flow->only(['title', 'version', 'status']);
        $flow->update($validated);

        if ($stepsData !== null) {
            $flow->steps()->delete();
            foreach ($stepsData as $s) {
                if (!empty(trim($s['title'] ?? ''))) {
                    $s['flow_id'] = $flow->id;
                    ApplicationFlowStep::create($s);
                }
            }
        }

        if ($request->hasFile('files')) {
            $this->saveFlowFiles($request->file('files'), $flow, $user->id, $request->input('file_description'));
        }

        ActivityLog::record(
            'flow_updated',
            'Flow Aplikasi',
            "Admin memperbarui flow aplikasi {$flow->flow_code} (Versi: {$flow->version})",
            'ApplicationFlow',
            $flow->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Flow aplikasi berhasil diperbarui.',
            'flow' => $flow->fresh()->load(['application', 'module', 'menu', 'steps', 'files.uploader']),
        ]);
    }

    public function uploadFile(Request $request, ApplicationFlow $flow): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'description' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'file|max:102400',
        ]);

        $uploaded = $this->saveFlowFiles($request->file('files'), $flow, $user->id, $request->input('description'));

        ActivityLog::record(
            'flow_file_uploaded',
            'Flow Aplikasi',
            "Admin mengunggah berkas dokumentasi pada flow {$flow->flow_code}",
            'ApplicationFlow',
            $flow->id,
            null,
            ['count' => count($uploaded)],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Berkas dokumentasi flow berhasil diunggah.',
            'files' => $uploaded,
        ]);
    }

    public function destroyFile(Request $request, ApplicationFlow $flow, ApplicationFlowFile $file): JsonResponse
    {
        if ((int) $file->flow_id !== (int) $flow->id) {
            return response()->json(['message' => 'Berkas tidak sesuai dengan flow ini.'], 404);
        }

        if (Storage::disk('local')->exists($file->file_path)) {
            Storage::disk('local')->delete($file->file_path);
        }

        $fileName = $file->original_name;
        $file->delete();

        ActivityLog::record(
            'flow_file_deleted',
            'Flow Aplikasi',
            "Admin menghapus berkas '{$fileName}' dari flow {$flow->flow_code}",
            'ApplicationFlow',
            $flow->id
        );

        return response()->json([
            'message' => 'Berkas berhasil dihapus.',
        ]);
    }

    private function saveFlowFiles(array $files, ApplicationFlow $flow, int $userId, ?string $description = null): array
    {
        $uploaded = [];
        foreach ($files as $file) {
            $path = $file->store('flows/documents', 'local');
            $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
            $f = ApplicationFlowFile::create([
                'flow_id' => $flow->id,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $ext ?: 'file',
                'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
                'file_size' => $file->getSize(),
                'description' => $description,
                'uploaded_by' => $userId,
            ]);
            $uploaded[] = $f;
        }
        return $uploaded;
    }

    public function destroy(ApplicationFlow $flow): JsonResponse
    {
        $code = $flow->flow_code;
        $flow->delete();

        ActivityLog::record(
            'flow_deleted',
            'Flow Aplikasi',
            "Admin menghapus flow aplikasi: {$code}",
            'ApplicationFlow',
            $flow->id,
            ['flow_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Flow aplikasi berhasil dihapus.',
        ]);
    }
}
