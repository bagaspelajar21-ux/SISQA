<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApplicationModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationModuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApplicationModule::with('application');

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $modules = $query->withCount(['menus', 'testCases', 'bugs'])
            ->orderBy('order_index')
            ->paginate($request->input('per_page', 20));

        return response()->json($modules);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ]);

        $validated['code'] = $this->generateCode();
        $module = ApplicationModule::create($validated);

        ActivityLog::record(
            'module_created',
            'Master Data',
            "Admin menambahkan modul baru: {$module->name} ({$module->code})",
            'ApplicationModule',
            $module->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Modul berhasil ditambahkan.',
            'module' => $module->load('application'),
        ], 201);
    }

    public function show(ApplicationModule $module): JsonResponse
    {
        return response()->json([
            'module' => $module->load(['application', 'menus'])->loadCount(['testCases', 'bugs']),
        ]);
    }

    public function update(Request $request, ApplicationModule $module): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ]);

        $old = $module->only(['name', 'description', 'order_index', 'is_active', 'application_id']);
        $module->update($validated);

        ActivityLog::record(
            'module_updated',
            'Master Data',
            "Admin memperbarui data modul: {$module->name}",
            'ApplicationModule',
            $module->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Data modul berhasil diperbarui.',
            'module' => $module->fresh()->load('application'),
        ]);
    }

    public function destroy(ApplicationModule $module): JsonResponse
    {
        $name = $module->name;
        $module->delete();

        ActivityLog::record(
            'module_deleted',
            'Master Data',
            "Admin menghapus modul: {$name}",
            'ApplicationModule',
            $module->id,
            ['name' => $name],
            null
        );

        return response()->json([
            'message' => 'Modul berhasil dihapus.',
        ]);
    }

    private function generateCode(): string
    {
        $nextId = (int) ApplicationModule::withTrashed()->max('id') + 1;

        do {
            $code = sprintf('MOD-%04d', $nextId);
            $nextId++;
        } while (ApplicationModule::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
