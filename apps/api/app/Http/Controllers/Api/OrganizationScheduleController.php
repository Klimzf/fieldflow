<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\IndexScheduleRequest;
use App\Http\Resources\ScheduleWorkOrderResource;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TenantAccessService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OrganizationScheduleController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(
        IndexScheduleRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        /** @var User $user */
        $user = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($user, $organization);

        $workOrders = WorkOrder::query()
            ->where('organization_id', $organization->id)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [
                $request->startDate(),
                $request->endDate(),
            ])
            ->with(['client', 'site', 'equipment', 'assignments.user'])
            ->orderBy('scheduled_at')
            ->get();

        return ScheduleWorkOrderResource::collection($workOrders);
    }
}
