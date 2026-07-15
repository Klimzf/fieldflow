<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Models\Site;
use App\Models\User;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class EquipmentController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, Site $site): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $site = $this->tenantAccess->findSiteForUser($user, $site);

        $equipment = $site
            ->equipment()
            ->orderBy('name')
            ->get();

        return EquipmentResource::collection($equipment);
    }

    public function store(StoreEquipmentRequest $request, Site $site): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $site = $this->tenantAccess->findSiteForUser($user, $site);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $site->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $equipment = $site
            ->equipment()
            ->create([
                ...$request->validated(),
                'organization_id' => $site->organization_id,
                'client_id' => $site->client_id,
            ]);

        return (new EquipmentResource($equipment))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Equipment $equipment): EquipmentResource
    {
        /** @var User $user */
        $user = $request->user();

        return new EquipmentResource(
            $this->tenantAccess->findEquipmentForUser($user, $equipment)
        );
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): EquipmentResource
    {
        /** @var User $user */
        $user = $request->user();

        $equipment = $this->tenantAccess->findEquipmentForUser($user, $equipment);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $equipment->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $equipment->update($request->validated());

        return new EquipmentResource($equipment->refresh());
    }
}
