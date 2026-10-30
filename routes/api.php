<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\ApplicationFlowController;
use App\Http\Controllers\Api\ApplicationMenuController;
use App\Http\Controllers\Api\ApplicationModuleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BugController;
use App\Http\Controllers\Api\CascadeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FileAccessController;
use App\Http\Controllers\Api\QaReportController;
use App\Http\Controllers\Api\QaScheduleController;
use App\Http\Controllers\Api\RetestController;
use App\Http\Controllers\Api\TestCaseController;
use App\Http\Controllers\Api\UsageGuideController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public routes & Native Browser File Preview/Download
Route::post('/login', [AuthController::class, 'login']);
Route::get('/files/{type}/{id}/download', [FileAccessController::class, 'download']);
Route::get('/files/{type}/{id}/preview', [FileAccessController::class, 'preview']);
Route::get('/files/{type}/{id}/content-preview', [FileAccessController::class, 'contentPreview']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    // Cascading lookups
    Route::prefix('cascade')->group(function () {
        Route::get('/apps', [CascadeController::class, 'applications']);
        Route::get('/modules', [CascadeController::class, 'modules']);
        Route::get('/menus', [CascadeController::class, 'menus']);
        Route::get('/flows', [CascadeController::class, 'flows']);
        Route::get('/users', [CascadeController::class, 'users']);
    });

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // QA Schedules
    Route::get('/schedules/calendar', [QaScheduleController::class, 'calendar']);
    Route::get('/schedules/check-conflict', [QaScheduleController::class, 'checkConflict']);
    Route::apiResource('schedules', QaScheduleController::class);

    // Test Cases & Executions
    Route::get('/test-cases/excel-template', [TestCaseController::class, 'downloadTemplate']);
    Route::post('/test-cases/import-excel', [TestCaseController::class, 'importExcel']);
    Route::post('/test-cases/{testCase}/execute', [TestCaseController::class, 'execute']);
    Route::apiResource('test-cases', TestCaseController::class);

    // Bug Tracking
    Route::get('/bugs/excel-template', [BugController::class, 'downloadTemplate']);
    Route::post('/bugs/import-excel', [BugController::class, 'importExcel']);
    Route::post('/bugs/{bug}/evidences', [BugController::class, 'uploadEvidence']);
    Route::apiResource('bugs', BugController::class);

    // Retesting
    Route::apiResource('retests', RetestController::class)->only(['index', 'store', 'show']);

    // Application Flows
    Route::post('/flows/{flow}/files', [ApplicationFlowController::class, 'uploadFile']);
    Route::delete('/flows/{flow}/files/{file}', [ApplicationFlowController::class, 'destroyFile']);
    Route::apiResource('flows', ApplicationFlowController::class);

    // Usage Guides
    Route::post('/guides/{guide}/observe', [UsageGuideController::class, 'observe']);
    Route::post('/guides/{guide}/files', [UsageGuideController::class, 'uploadFile']);
    Route::apiResource('guides', UsageGuideController::class);

    // QA Report (Centralized export ONLY here)
    Route::get('/reports/summary', [QaReportController::class, 'summary']);
    Route::get('/reports/export/excel', [QaReportController::class, 'exportExcel']);
    Route::get('/reports/export/pdf', [QaReportController::class, 'exportPdf']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // Admin Only
    Route::middleware('admin')->group(function () {
        // Users & Role Access Checklist Management
        Route::get('/role-permissions', [UserController::class, 'getRolePermissions']);
        Route::put('/role-permissions', [UserController::class, 'updateRolePermissions']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::apiResource('users', UserController::class);

        // Master Application, Modules, Menus
        Route::apiResource('applications', ApplicationController::class);
        Route::apiResource('modules', ApplicationModuleController::class);
        Route::apiResource('menus', ApplicationMenuController::class);
    });
});
