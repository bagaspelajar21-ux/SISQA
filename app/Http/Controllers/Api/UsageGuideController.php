<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\UsageGuide;
use App\Models\UsageGuideFile;
use App\Models\UsageGuideObservation;
use App\Models\UsageGuideStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsageGuideController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = UsageGuide::with(['creator', 'lastObserver']);

        if ($user->role !== 'admin') {
            $query->where('status', 'Published');
        } elseif ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($obsStatus = $request->input('observation_status')) {
            $query->where('observation_status', $obsStatus);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($menuExact = $request->input('menu_name')) {
            $guide = UsageGuide::with(['steps', 'files.uploader', 'observations.admin', 'lastObserver', 'creator'])
                ->where('menu_name', $menuExact)
                ->first();

            return response()->json([
                'guide' => $guide,
            ]);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('menu_name', 'like', "%{$search}%")
                  ->orWhere('guide_code', 'like', "%{$search}%");
            });
        }

        $guides = $query->withCount(['steps', 'files', 'observations'])
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($guides);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'menu_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'menu_purpose' => 'nullable|string',
            'menu_function' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'tips' => 'nullable|string',
            'troubleshooting' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'steps' => 'nullable|array',
            'steps.*.step_number' => 'required|integer',
            'steps.*.title' => 'required|string',
            'steps.*.description' => 'required|string',
            'steps.*.notes' => 'nullable|string',
        ]);

        $prefix = 'GUIDE';
        $count = UsageGuide::count();
        $validated['guide_code'] = sprintf("%s-%04d", $prefix, $count + 1);
        $validated['created_by'] = $user->id;
        $validated['version'] = $validated['version'] ?? '1.0';
        $validated['observation_status'] = 'Belum Diamati';

        $stepsData = $validated['steps'] ?? [];
        unset($validated['steps']);

        $guide = UsageGuide::create($validated);

        if (!empty($stepsData)) {
            foreach ($stepsData as $s) {
                $s['usage_guide_id'] = $guide->id;
                UsageGuideStep::create($s);
            }
        }

        ActivityLog::record(
            'guide_created',
            'Panduan SISQA',
            "Admin membuat panduan penggunaan baru: {$guide->title} ({$guide->guide_code})",
            'UsageGuide',
            $guide->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Panduan SISQA berhasil dibuat.',
            'guide' => $guide->load(['creator', 'steps']),
        ], 201);
    }

    public function show(UsageGuide $guide): JsonResponse
    {
        return response()->json([
            'guide' => $guide->load([
                'steps',
                'files.uploader',
                'observations.admin',
                'lastObserver',
                'creator',
                'updater',
            ]),
        ]);
    }

    public function update(Request $request, UsageGuide $guide): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'menu_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'menu_purpose' => 'nullable|string',
            'menu_function' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'tips' => 'nullable|string',
            'troubleshooting' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'status' => ['required', Rule::in(['Draft', 'Published', 'Archived'])],
            'steps' => 'nullable|array',
            'steps.*.step_number' => 'required|integer',
            'steps.*.title' => 'required|string',
            'steps.*.description' => 'required|string',
            'steps.*.notes' => 'nullable|string',
        ]);

        $stepsData = $validated['steps'] ?? null;
        unset($validated['steps']);

        $validated['updated_by'] = $user->id;

        // CRUCIAL RULE: If content or version is modified, observation status resets to 'Belum Diamati'
        $validated['observation_status'] = 'Belum Diamati';

        $old = $guide->only(['title', 'version', 'status', 'observation_status']);
        $guide->update($validated);

        if ($stepsData !== null) {
            $guide->steps()->delete();
            foreach ($stepsData as $s) {
                $s['usage_guide_id'] = $guide->id;
                UsageGuideStep::create($s);
            }
        }

        ActivityLog::record(
            'guide_updated',
            'Panduan SISQA',
            "Admin memperbarui panduan {$guide->guide_code}. Status pengamatan direset menjadi 'Belum Diamati'.",
            'UsageGuide',
            $guide->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Panduan berhasil diperbarui. Status pengamatan kembali ke Belum Diamati.',
            'guide' => $guide->fresh()->load(['steps', 'lastObserver']),
        ]);
    }

    public function observe(Request $request, UsageGuide $guide): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Hanya Administrator yang memiliki wewenang untuk menandai panduan sudah diamati.',
            ], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $observedAt = now();
        $observedVersion = $guide->version;

        UsageGuideObservation::create([
            'usage_guide_id' => $guide->id,
            'admin_id' => $user->id,
            'observed_version' => $observedVersion,
            'observed_at' => $observedAt,
            'notes' => $validated['notes'] ?? null,
        ]);

        $guide->update([
            'observation_status' => 'Sudah Diamati',
            'last_observed_by' => $user->id,
            'last_observed_at' => $observedAt,
            'last_observed_version' => $observedVersion,
        ]);

        ActivityLog::record(
            'guide_observed',
            'Panduan SISQA',
            "Admin {$user->name} menandai panduan '{$guide->title}' ({$guide->guide_code}) sebagai 'Sudah Diamati' pada versi {$observedVersion}.",
            'UsageGuide',
            $guide->id,
            ['observation_status' => 'Belum Diamati'],
            ['observation_status' => 'Sudah Diamati', 'admin_id' => $user->id, 'version' => $observedVersion]
        );

        return response()->json([
            'message' => 'Panduan berhasil ditandai sebagai Sudah Diamati oleh Admin.',
            'guide' => $guide->fresh()->load(['lastObserver', 'observations.admin']),
        ]);
    }

    public function uploadFile(Request $request, UsageGuide $guide): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'description' => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'file|max:51200|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,webp',
        ]);

        $uploaded = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('guides/files', 'local');
            $f = UsageGuideFile::create([
                'usage_guide_id' => $guide->id,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'description' => $request->description,
                'uploaded_by' => $user->id,
            ]);
            $uploaded[] = $f;
        }

        ActivityLog::record(
            'guide_file_uploaded',
            'Panduan SISQA',
            "Admin mengunggah lampiran dokumen/screenshot pada panduan {$guide->guide_code}",
            'UsageGuide',
            $guide->id,
            null,
            ['count' => count($uploaded)],
            'Success',
            $user->id
        );

        return response()->json([
            'message' => 'Berkas panduan berhasil diunggah.',
            'files' => $uploaded,
        ]);
    }

    public function destroy(UsageGuide $guide): JsonResponse
    {
        $code = $guide->guide_code;
        $guide->delete();

        ActivityLog::record(
            'guide_deleted',
            'Panduan SISQA',
            "Admin menghapus panduan SISQA: {$code}",
            'UsageGuide',
            $guide->id,
            ['guide_code' => $code],
            null
        );

        return response()->json([
            'message' => 'Panduan berhasil dihapus.',
        ]);
    }
}
