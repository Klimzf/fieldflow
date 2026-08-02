<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\IndexSiteRequest;
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

    public function index(IndexSiteRequest $request, Client $client): AnonymousResourceCollection
    {
        $user = $request->user();

        $client = $this->tenantAccess->findClientForUser($user, $client);

        $sitesQuery = $client
            ->sites()
            ->orderBy('name')
            ->latest();

        $searchQuery = $request->searchQuery();

        if ($searchQuery !== null) {
            $sitesQuery->where(function ($query) use ($searchQuery): void {
                $query
                    ->where('name', 'ilike', "%{$searchQuery}%")
                    ->orWhere('address', 'ilike', "%{$searchQuery}%")
                    ->orWhere('contact_name', 'ilike', "%{$searchQuery}%")
                    ->orWhere('contact_phone', 'ilike', "%{$searchQuery}%")
                    ->orWhere('notes', 'ilike', "%{$searchQuery}%");
            });
        }

        $sites = $sitesQuery
            ->paginate($request->perPage())
            ->withQueryString();

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
