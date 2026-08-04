<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\OrganizationDashboardController;
use App\Http\Controllers\Api\OrganizationMemberController;
use App\Http\Controllers\Api\OrganizationScheduleController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\WorkOrderAssignableUserController;
use App\Http\Controllers\Api\WorkOrderAssignmentController;
use App\Http\Controllers\Api\WorkOrderChecklistItemController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\WorkOrderFileController;
use App\Http\Controllers\Api\WorkOrderServiceReportController;
use App\Http\Controllers\Api\WorkOrderUpdateController;
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

    Route::apiResource('work-orders.updates', WorkOrderUpdateController::class)
        ->only(['index', 'store']);

    Route::get('work-orders/{workOrder}/assignable-users', [WorkOrderAssignableUserController::class, 'index']);

    Route::get('work-orders/{workOrder}/assignments', [WorkOrderAssignmentController::class, 'index']);
    Route::post('work-orders/{workOrder}/assignments', [WorkOrderAssignmentController::class, 'store']);
    Route::delete('work-order-assignments/{workOrderAssignment}', [WorkOrderAssignmentController::class, 'destroy']);
    Route::get('work-orders/{workOrder}/service-report/download', [WorkOrderServiceReportController::class, 'download']);

    Route::get('organizations/{organization}/members', [OrganizationMemberController::class, 'index']);
    Route::post('organizations/{organization}/members', [OrganizationMemberController::class, 'store']);
    Route::patch('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'update']);
    Route::delete('organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy']);

    Route::get('organizations/{organization}/dashboard', [OrganizationDashboardController::class, 'show']);
    Route::get('organizations/{organization}/schedule', [OrganizationScheduleController::class, 'index']);

    Route::get('work-orders/{workOrder}/checklist-items', [WorkOrderChecklistItemController::class, 'index']);
    Route::post('work-orders/{workOrder}/checklist-items', [WorkOrderChecklistItemController::class, 'store']);
    Route::patch('work-order-checklist-items/{workOrderChecklistItem}/completion', [WorkOrderChecklistItemController::class, 'updateCompletion']);
    Route::delete('work-order-checklist-items/{workOrderChecklistItem}', [WorkOrderChecklistItemController::class, 'destroy']);

    Route::get('work-orders/{workOrder}/files', [WorkOrderFileController::class, 'index']);
    Route::post('work-orders/{workOrder}/files', [WorkOrderFileController::class, 'store']);
    Route::get('work-order-files/{workOrderFile}/download', [WorkOrderFileController::class, 'download']);
    Route::delete('work-order-files/{workOrderFile}', [WorkOrderFileController::class, 'destroy']);
});
