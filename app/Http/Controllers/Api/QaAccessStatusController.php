<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QaScheduleLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QaAccessStatusController extends Controller
{
    public function show(Request $request, QaScheduleLockService $scheduleLock): JsonResponse
    {
        $validated = $request->validate([
            'module_id' => [
                'required',
                'integer',
                Rule::exists('application_modules', 'id')->whereNull('deleted_at'),
            ],
        ]);

        return response()->json([
            'status' => $scheduleLock->statusForModule((int) $validated['module_id']),
        ]);
    }
}
