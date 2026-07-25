<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderChecklistItem\StoreWorkOrderChecklistItemRequest;
use App\Http\Requests\WorkOrderChecklistItem\UpdateWorkOrderChecklistItemCompletionRequest;
use App\Http\Resources\WorkOrderChecklistItemResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklistItem;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WorkOrderChecklistItemController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, WorkOrder $workOrder): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $items = $workOrder
            ->checklistItems()
            ->with('completedBy')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return WorkOrderChecklistItemResource::collection($items);
    }

    public function store(
        StoreWorkOrderChecklistItemRequest $request,
        WorkOrder $workOrder,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $workOrder->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $validated = $request->validated();

        $position = $validated['position']
            ?? ((int) $workOrder->checklistItems()->max('position') + 1);

        $item = $workOrder
            ->checklistItems()
            ->create([
                'organization_id' => $workOrder->organization_id,
                'title' => $validated['title'],
                'is_completed' => false,
                'completed_by_id' => null,
                'completed_at' => null,
                'position' => $position,
            ]);

        return (new WorkOrderChecklistItemResource($item->load('completedBy')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function updateCompletion(
        UpdateWorkOrderChecklistItemCompletionRequest $request,
        WorkOrderChecklistItem $workOrderChecklistItem,
    ): WorkOrderChecklistItemResource {
        /** @var User $user */
        $user = $request->user();

        $workOrderChecklistItem = $this->tenantAccess->findWorkOrderChecklistItemForUser(
            $user,
            $workOrderChecklistItem,
        );

        $isCompleted = (bool) $request->validated('is_completed');

        $workOrderChecklistItem->update([
            'is_completed' => $isCompleted,
            'completed_by_id' => $isCompleted ? $user->id : null,
            'completed_at' => $isCompleted ? now() : null,
        ]);

        return new WorkOrderChecklistItemResource(
            $workOrderChecklistItem->refresh()->load('completedBy')
        );
    }

    public function destroy(
        Request $request,
        WorkOrderChecklistItem $workOrderChecklistItem,
    ): Response {
        /** @var User $user */
        $user = $request->user();

        $workOrderChecklistItem = $this->tenantAccess->findWorkOrderChecklistItemForUser(
            $user,
            $workOrderChecklistItem,
        );

        $organization = $this->tenantAccess->findOrganizationForUser(
            $user,
            $workOrderChecklistItem->organization,
        );

        $this->tenantAccess->assertCanManageOrganization($organization);

        $workOrderChecklistItem->delete();

        return response()->noContent();
    }
}
