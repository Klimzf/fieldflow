<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignableUserResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class WorkOrderAssignableUserController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, WorkOrder $workOrder): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $workOrder = $this->tenantAccess->findWorkOrderForUser($user, $workOrder);

        $users = $workOrder
            ->organization
            ->users()
            ->orderBy('name')
            ->get();

        return AssignableUserResource::collection($users);
    }
}
