<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});

// Public report submission (for landing page)
Route::post('/reports/public', [ReportController::class, 'storePublic']);

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Reports - accessible by authenticated users
    Route::apiResource('reports', ReportController::class);

    // Dashboard stats
    Route::get('/dashboard/stats', [ReportController::class, 'stats']);

    // Status update - will check role in controller
    Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']);

    // Bulk actions - will check role in controller
    Route::post('/reports/bulk/status', [ReportController::class, 'bulkUpdateStatus']);
    Route::post('/reports/bulk/delete', [ReportController::class, 'bulkDelete']);
});
