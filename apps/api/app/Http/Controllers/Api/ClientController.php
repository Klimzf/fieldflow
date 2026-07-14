<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ClientController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($user, $organization);

        $clients = $organization
            ->clients()
            ->orderBy('name')
            ->get();

        return ClientResource::collection($clients);
    }

    public function store(StoreClientRequest $request, Organization $organization): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($user, $organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $client = $organization
            ->clients()
            ->create($request->validated());

        return (new ClientResource($client))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Client $client): ClientResource
    {
        return new ClientResource(
            $this->findClientForUser($request, $client)
        );
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        /** @var User $user */
        $user = $request->user();

        $client = $this->findClientForUser($request, $client);

        $organization = $this->tenantAccess->findOrganizationForUser($user, $client->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $client->update($request->validated());

        return new ClientResource($client->refresh());
    }

    private function findClientForUser(Request $request, Client $client): Client
    {
        $organizationIds = $request
            ->user()
            ->organizations()
            ->pluck('organizations.id');

        return Client::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereKey($client->id)
            ->firstOrFail();
    }
}
