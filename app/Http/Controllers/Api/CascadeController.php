<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationFlow;
use App\Models\ApplicationMenu;
use App\Models\ApplicationModule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CascadeController extends Controller
{
    public function applications(): JsonResponse
    {
        $apps = Application::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'version', 'environment']);

        return response()->json($apps);
    }

    public function modules(Request $request): JsonResponse
    {
        $query = ApplicationModule::where('is_active', true);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        $modules = $query->orderBy('order_index')->get(['id', 'application_id', 'name', 'code']);

        return response()->json($modules);
    }

    public function menus(Request $request): JsonResponse
    {
        $query = ApplicationMenu::where('is_active', true);

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        $menus = $query->orderBy('order_index')->get(['id', 'application_id', 'module_id', 'name', 'code']);

        return response()->json($menus);
    }

    public function flows(Request $request): JsonResponse
    {
        $query = ApplicationFlow::with('steps');

        if ($appId = $request->input('application_id')) {
            $query->where('application_id', $appId);
        }

        if ($modId = $request->input('module_id')) {
            $query->where('module_id', $modId);
        }

        $flows = $query->orderBy('title')->get(['id', 'application_id', 'module_id', 'flow_code', 'title', 'version', 'status']);

        return response()->json($flows);
    }

    public function users(): JsonResponse
    {
        $users = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email', 'role']);

        return response()->json($users);
    }
}
