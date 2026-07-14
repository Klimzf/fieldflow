<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreSiteRequest;
use App\Http\Requests\Site\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Client;
use App\Models\Site;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class SiteController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, Client $client): AnonymousResourceCollection
    {
        $user = $request->user();

        $client = $this->tenantAccess->findClientForUser($user, $client);

        $sites = $client
            ->sites()
            ->orderBy('name')
            ->get();

        return SiteResource::collection($sites);
    }

    public function store(StoreSiteRequest $request, Client $client): JsonResponse
    {
        $user = $request->user();

        $client = $this->tenantAccess->findClientForUser($user, $client);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $client->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $site = $client
            ->sites()
            ->create([
                ...$request->validated(),
                'organization_id' => $organization->id,
            ]);

        return (new SiteResource($site))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Site $site): SiteResource
    {
        $user = $request->user();

        return new SiteResource(
            $this->tenantAccess->findSiteForUser($user, $site)
        );
    }

    public function update(UpdateSiteRequest $request, Site $site): SiteResource
    {
        $user = $request->user();

        $site = $this->tenantAccess->findSiteForUser($user, $site);
        $organization = $this->tenantAccess->findOrganizationForUser($user, $site->organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $site->update($request->validated());

        return new SiteResource($site->refresh());
    }
}
