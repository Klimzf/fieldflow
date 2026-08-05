<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrder\IndexWorkOrderRequest;
use App\Http\Requests\WorkOrder\StoreWorkOrderRequest;
use App\Http\Requests\WorkOrder\UpdateWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Models\Equipment;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantAccessService;
use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WorkOrderController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
        private readonly UserNotificationService $notification,
    ) {}

    public function index(IndexWorkOrderRequest $request, Site $site): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $site = $this->tenantAccess->findSiteForUser($user, $site);

        $workOrdersQuery = $site
            ->workOrders()
            ->with(['client', 'site', 'equipment'])
            ->orderByDesc('created_at');

        if ($request->searchQuery() !== null) {
            $searchQuery = $request->searchQuery();

            $workOrdersQuery->where(function ($query) use ($searchQuery): void {
                $query
                    ->where('title', 'ilike', "%{$searchQuery}%")
                    ->orWhere('description', 'ilike', "%{$searchQuery}%");
            });
        }

        if ($request->status() !== null) {
            $workOrdersQuery->where('status', $request->status());
        }

        if ($request->priority() !== null) {
            $workOrdersQuery->where('priority', $request->priority());
        }

        $workOrders = $workOrdersQuery->paginate($request->perPage());

        return WorkOrderResource::collection($workOrders);
    }

    public function store(StoreWorkOrderRequest $request, Site $site): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $site = $this->tenantAccess->findSiteForUser($user, $site);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $site->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $validated = $request->validated();

        $equipmentId = $validated['equipment_id'] ?? null;

        if ($equipmentId !== null) {
            Equipment::query()
                ->whereKey($equipmentId)
                ->where('site_id', $site->id)
                ->firstOrFail();
        }

        $workOrder = $site
            ->workOrders()
            ->create([
                ...$validated,
                'organization_id' => $site->organization_id,
                'client_id' => $site->client_id,
            ]);

        $this->notification->notifyWorkOrderCreated($workOrder, $user);

        $workOrder->updates()->create([
            'organization_id' => $workOrder->organization_id,
            'user_id' => $user->id,
            'type' => 'created',
            'new_status' => $workOrder->status,
        ]);

        return (new WorkOrderResource($workOrder))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, WorkOrder $workOrder): WorkOrderResource
    {
        /** @var User $user */
        $user = $request->user();

        return new WorkOrderResource(
            $this->tenantAccess->findWorkOrderForUser($user, $workOrder)
        );
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $workOrder): WorkOrderResource
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $workOrder->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $validated = $request->validated();

        $equipmentId = $validated['equipment_id'] ?? null;

        if (array_key_exists('equipment_id', $validated) && $equipmentId !== null) {
            Equipment::query()
                ->whereKey($equipmentId)
                ->where('site_id', $workOrder->site_id)
                ->firstOrFail();
        }

        $oldStatus = $workOrder->status;

        $workOrder->update($validated);

        if (
            array_key_exists('status', $validated)
            && $validated['status'] !== $oldStatus
        ) {
            $workOrder->updates()->create([
                'organization_id' => $workOrder->organization_id,
                'user_id' => $user->id,
                'type' => 'status_changed',
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
            ]);

            $this->notification->notifyWorkOrderStatusChanged($workOrder, $user, $oldStatus, $workOrder->status);
        }

        return new WorkOrderResource($workOrder->refresh());
    }
}
