<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderUpdate\StoreWorkOrderUpdateRequest;
use App\Http\Resources\WorkOrderUpdateResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class WorkOrderUpdateController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, WorkOrder $workOrder): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $updates = $workOrder
            ->updates()
            ->with('user')
            ->oldest()
            ->get();

        return WorkOrderUpdateResource::collection($updates);
    }

    public function store(StoreWorkOrderUpdateRequest $request, WorkOrder $workOrder): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $update = $workOrder
            ->updates()
            ->create([
                'organization_id' => $workOrder->organization_id,
                'user_id' => $user->id,
                'type' => 'comment',
                'message' => $request->validated('message'),
            ]);

        return (new WorkOrderUpdateResource($update->load('user')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
