<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)
    ->name('api.health');

Route::prefix('auth')
    ->name('auth.')
    ->group(function (): void {
        Route::middleware('throttle:10,1')->group(function (): void {
            Route::post('/register', [AuthController::class, 'register'])
                ->name('register');

            Route::post('/login', [AuthController::class, 'login'])
                ->name('login');
        });

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/user', [AuthController::class, 'user'])
                ->name('user');

            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('logout');
        });
    });

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('organizations', OrganizationController::class)
        ->except(['destroy']);

    Route::get('/organizations/{organization}/clients', [ClientController::class, 'index'])
        ->name('organizations.clients.index');

    Route::post('/organizations/{organization}/clients', [ClientController::class, 'store'])
        ->name('organization.clients.store');

    Route::get('/clients/{client}', [ClientController::class, 'show'])
        ->name('clients.show');

    Route::patch('/clients/{client}', [ClientController::class, 'update'])
        ->name('clients.update');

    Route::apiResource('clients.sites', SiteController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update']);

    Route::apiResource('sites.equipment', EquipmentController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update']);

    Route::apiResource('sites.work-orders', WorkOrderController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update']);
});
