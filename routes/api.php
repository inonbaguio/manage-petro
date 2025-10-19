<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Shared\Tenancy\TenantMiddleware;
use App\Modules\Auth\Http\{LoginController, MeController};
use App\Modules\Clients\Http\Controllers\ClientController;
use App\Modules\Locations\Http\Controllers\LocationController;
use App\Modules\Trucks\Http\Controllers\TruckController;
use App\Modules\Orders\Http\Controllers\{OrderController, OrderActionsController};
use App\Modules\Dashboard\Http\Controllers\DashboardController;
use App\Modules\ActivityLog\Http\Controllers\ActivityLogController;

// All API routes are prefixed with {tenant} and use TenantMiddleware
Route::prefix('{tenant}')->middleware([TenantMiddleware::class])->group(function () {

    // Auth routes (no auth required)
    Route::post('auth/login', [LoginController::class, 'login']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth endpoints
        Route::post('auth/logout', [LoginController::class, 'logout']);
        Route::get('auth/me', [MeController::class, 'show']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Activity Logs (ADMIN only)
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/{modelType}/{modelId}', [ActivityLogController::class, 'getByModel']);

        // Resource endpoints
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('locations', LocationController::class);
        Route::apiResource('trucks', TruckController::class);
        Route::apiResource('orders', OrderController::class);

        // Truck actions
        Route::post('trucks/{id}/toggle-active', [TruckController::class, 'toggleActive']);

        // Order lifecycle actions
        Route::post('orders/{id}/submit', [OrderActionsController::class, 'submit']);
        Route::post('orders/{id}/schedule', [OrderActionsController::class, 'schedule']);
        Route::post('orders/{id}/dispatch', [OrderActionsController::class, 'dispatch']);
        Route::post('orders/{id}/deliver', [OrderActionsController::class, 'deliver']);
        Route::post('orders/{id}/cancel', [OrderActionsController::class, 'cancel']);
    });
});
