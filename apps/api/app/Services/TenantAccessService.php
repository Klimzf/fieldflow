<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Response;

final class TenantAccessService
{
    public function findOrganizationForUser(User $user, Organization $organization): Organization
    {
        return $user
            ->organizations()
            ->whereKey($organization->id)
            ->firstOrFail();
    }

    public function findClientForUser(User $user, Client $client): Client
    {
        $organizationIds = $user
            ->organizations()
            ->pluck('organizations.id');

        return Client::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereKey($client->id)
            ->firstOrFail();
    }

    public function findSiteForUser(User $user, Site $site): Site
    {
        $organizationIds = $user
            ->organizations()
            ->pluck('organizations.id');

        return Site::query()
            ->whereIn('organization_id', $organizationIds)
            ->whereKey($site->id)
            ->firstOrFail();
    }

    public function assertCanManageOrganization(Organization $organization): void
    {
        abort_if(
            ! in_array($organization->pivot->role, ['owner', 'admin'], true),
            Response::HTTP_FORBIDDEN
        );
    }
}
