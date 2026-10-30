<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ApplicationMenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApplicationMenu::with(['application', 'module']);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('url_or_route', 'like', "%{$search}%");
            });
        }

        $menus = $query->withCount(['testCases', 'bugs'])
            ->orderBy('order_index')
            ->paginate($request->input('per_page', 25));

        return response()->json($menus);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'required|exists:application_modules,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('application_menus')->where(fn($q) => $q->where('module_id', $request->module_id)),
            ],
            'url_or_route' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ]);

        // Validate module belongs to application
        $module = ApplicationModule::where('id', $validated['module_id'])
            ->where('application_id', $validated['application_id'])
            ->first();

        if (!$module) {
            throw ValidationException::withMessages([
                'module_id' => ['Modul yang dipilih tidak terdaftar pada aplikasi ini.'],
            ]);
        }

        $menu = ApplicationMenu::create($validated);

        ActivityLog::record(
            'menu_created',
            'Master Data',
            "Admin menambahkan menu baru: {$menu->name} ({$menu->code})",
            'ApplicationMenu',
            $menu->id,
            null,
            $validated
        );

        return response()->json([
            'message' => 'Menu berhasil ditambahkan.',
            'menu' => $menu->load(['application', 'module']),
        ], 201);
    }

    public function show(ApplicationMenu $menu): JsonResponse
    {
        return response()->json([
            'menu' => $menu->load(['application', 'module'])->loadCount(['testCases', 'bugs']),
        ]);
    }

    public function update(Request $request, ApplicationMenu $menu): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'module_id' => 'required|exists:application_modules,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('application_menus')
                    ->where(fn($q) => $q->where('module_id', $request->module_id))
                    ->ignore($menu->id),
            ],
            'url_or_route' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean',
        ]);

        // Validate module belongs to application
        $module = ApplicationModule::where('id', $validated['module_id'])
            ->where('application_id', $validated['application_id'])
            ->first();

        if (!$module) {
            throw ValidationException::withMessages([
                'module_id' => ['Modul yang dipilih tidak terdaftar pada aplikasi ini.'],
            ]);
        }

        $old = $menu->only(['name', 'code', 'url_or_route', 'description', 'order_index', 'is_active', 'application_id', 'module_id']);
        $menu->update($validated);

        ActivityLog::record(
            'menu_updated',
            'Master Data',
            "Admin memperbarui data menu: {$menu->name}",
            'ApplicationMenu',
            $menu->id,
            $old,
            $validated
        );

        return response()->json([
            'message' => 'Data menu berhasil diperbarui.',
            'menu' => $menu->fresh()->load(['application', 'module']),
        ]);
    }

    public function destroy(ApplicationMenu $menu): JsonResponse
    {
        $name = $menu->name;
        $menu->delete();

        ActivityLog::record(
            'menu_deleted',
            'Master Data',
            "Admin menghapus menu: {$name}",
            'ApplicationMenu',
            $menu->id,
            ['name' => $name],
            null
        );

        return response()->json([
            'message' => 'Menu berhasil dihapus.',
        ]);
    }
}
