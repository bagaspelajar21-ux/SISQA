<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dateRange = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $user = $request->user();
        $query = ActivityLog::with('user');

        // CRUCIAL RULE: Admin sees all; User sees ONLY their own logs
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        } elseif ($filterUserId = $request->input('user_id')) {
            $query->where('user_id', $filterUserId);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($startDate = $dateRange['start_date'] ?? null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $dateRange['end_date'] ?? null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));

        return response()->json($logs);
    }
}
