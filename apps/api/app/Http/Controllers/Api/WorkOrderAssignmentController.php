<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkOrderAssignment\StoreWorkOrderAssignmentRequest;
use App\Http\Resources\WorkOrderAssignmentResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class WorkOrderAssignmentController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, WorkOrder $workOrder): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $assignments = $workOrder
            ->assignments()
            ->with(['user', 'assignedBy'])
            ->oldest()
            ->get();

        return WorkOrderAssignmentResource::collection($assignments);
    }

    public function store(StoreWorkOrderAssignmentRequest $request, WorkOrder $workOrder): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $workOrder->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $assignedUser = $organization
            ->users()
            ->whereKey($request->validated('user_id'))
            ->firstOrFail();

        $assignmentExists = $workOrder
            ->assignments()
            ->where('user_id', $assignedUser->id)
            ->exists();

        if ($assignmentExists) {
            throw ValidationException::withMessages([
                'user_id' => ['Этот пользователь уже назначен на заявку.'],
            ]);
        }

        $assignment = $workOrder
            ->assignments()
            ->create([
                'organization_id' => $workOrder->organization_id,
                'user_id' => $assignedUser->id,
                'assigned_by_id' => $user->id,
            ]);

        return (new WorkOrderAssignmentResource($assignment->load(['user', 'assignedBy'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, WorkOrderAssignment $workOrderAssignment): Response
    {
        /** @var User $user */
        $user = $request->user();

        $workOrderAssignment = $this->tenantAccess->findWorkOrderAssignmentForUser(
            $user,
            $workOrderAssignment,
        );

        $organization = $this->tenantAccess->findOrganizationForUser(
            $user,
            $workOrderAssignment->organization,
        );

        $this->tenantAccess->assertCanManageOrganization($organization);

        $workOrderAssignment->delete();

        return response()->noContent();
    }
}
