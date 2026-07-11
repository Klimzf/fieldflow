<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Response;

final class OrganizationAccessService
{
    public function findForUser(User $user, Organization $organization): Organization
    {
        return $user
            ->organizations()
            ->whereKey($organization->id)
            ->firstOrFail();
    }

    public function assertCanManage(Organization $organization): void
    {
        abort_if(
            ! in_array($organization->pivot->role, ['owner', 'admin'], true),
            Response::HTTP_FORBIDDEN
        );
    }
}
