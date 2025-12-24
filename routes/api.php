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

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        // Add admin-specific routes here in the future
    });

    // Moderator and Admin routes
    Route::middleware('role:admin|moderator')->group(function () {
        // Routes for report status management
        Route::patch('/reports/{report}/status', [ReportController::class, 'updateStatus']);
    });
});
