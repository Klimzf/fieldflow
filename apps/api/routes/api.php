<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\HealthController;
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
