<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkOrderResource;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrganizationDashboardController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function show(Request $request, Organization $organization): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($user, $organization);

        $workOrdersByStatus = WorkOrder::query()
            ->where('organization_id', $organization->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $latestWorkOrders = WorkOrder::query()
            ->where('organization_id', $organization->id)
            ->latest()
            ->limit(5)
            ->get();

        $assignedToMeWorkOrders = WorkOrder::query()
            ->where('organization_id', $organization->id)
            ->whereHas('assignments', function ($query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'clients_count' => Client::query()
                    ->where('organization_id', $organization->id)
                    ->count(),

                'sites_count' => Site::query()
                    ->where('organization_id', $organization->id)
                    ->count(),

                'equipment_count' => Equipment::query()
                    ->where('organization_id', $organization->id)
                    ->count(),

                'work_orders_count' => WorkOrder::query()
                    ->where('organization_id', $organization->id)
                    ->count(),

                'work_orders_by_status' => [
                    'new' => (int) ($workOrdersByStatus['new'] ?? 0),
                    'in_progress' => (int) ($workOrdersByStatus['in_progress'] ?? 0),
                    'completed' => (int) ($workOrdersByStatus['completed'] ?? 0),
                    'cancelled' => (int) ($workOrdersByStatus['cancelled'] ?? 0),
                ],

                'urgent_work_orders_count' => WorkOrder::query()
                    ->where('organization_id', $organization->id)
                    ->where('priority', 'urgent')
                    ->count(),

                'assigned_to_me_count' => WorkOrderAssignment::query()
                    ->where('organization_id', $organization->id)
                    ->where('user_id', $user->id)
                    ->count(),

                'latest_work_orders' => WorkOrderResource::collection($latestWorkOrders)
                    ->resolve($request),

                'assigned_to_me_work_orders' => WorkOrderResource::collection($assignedToMeWorkOrders)
                    ->resolve($request),
            ],
        ]);
    }
}
