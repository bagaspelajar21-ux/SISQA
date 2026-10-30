<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Application::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $apps = $query->withCount(['modules', 'menus', 'testCases', 'bugs', 'schedules'])
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return response()->json($apps);
    }

    public function all(): JsonResponse
    {
        $apps = Application::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code', 'version']);
        return response()->json($apps);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:applications,code',
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'environment' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $app = Application::create($validated);

        ActivityLog::record(
            'application_created',
            'Master Data',
            "Admin menambahkan aplikasi baru: {$app->name} ({$app->code})",
            'Application',
            $app->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Aplikasi berhasil ditambahkan.',
            'application' => $app,
        ], 201);
    }

    public function show(Application $application): JsonResponse
    {
        return response()->json([
            'application' => $application->load([
                'modules' => fn($q) => $q->withCount(['menus', 'testCases', 'bugs']),
                'menus',
            ])->loadCount(['testCases', 'bugs', 'schedules']),
        ]);
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('applications')->ignore($application->id)],
            'description' => 'nullable|string',
            'version' => 'nullable|string|max:50',
            'environment' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $old = $application->only(['name', 'code', 'description', 'version', 'environment', 'is_active']);
        $application->update($validated);

        ActivityLog::record(
            'application_updated',
            'Master Data',
            "Admin memperbarui data aplikasi: {$application->name}",
            'Application',
            $application->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Data aplikasi berhasil diperbarui.',
            'application' => $application->fresh(),
        ]);
    }

    public function destroy(Application $application): JsonResponse
    {
        $name = $application->name;
        $application->delete();

        ActivityLog::record(
            'application_deleted',
            'Master Data',
            "Admin menghapus aplikasi: {$name}",
            'Application',
            $application->id,
            ['name' => $name],
            null
        );

        return response()->json([
            'message' => 'Aplikasi berhasil dihapus.',
        ]);
    }
}
