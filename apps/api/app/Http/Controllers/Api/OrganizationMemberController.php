<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationMember\StoreOrganizationMemberRequest;
use App\Http\Requests\OrganizationMember\UpdateOrganizationMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\TenantAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

final class OrganizationMemberController extends Controller
{
    public function __construct(
        private readonly TenantAccessService $tenantAccess,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($currentUser, $organization);

        $members = $organization
            ->users()
            ->orderBy('name')
            ->get();

        return OrganizationMemberResource::collection($members);
    }

    public function store(
        StoreOrganizationMemberRequest $request,
        Organization $organization,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($currentUser, $organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $member = User::query()
            ->where('email', $request->validated('email'))
            ->firstOrFail();

        $alreadyMember = $organization
            ->users()
            ->whereKey($member->id)
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => ['Этот пользователь уже состоит в организации.'],
            ]);
        }

        $organization->users()->attach($member->id, [
            'role' => $request->validated('role'),
        ]);

        $member = $this->findMemberInOrganization($organization, $member);

        return (new OrganizationMemberResource($member))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateOrganizationMemberRequest $request,
        Organization $organization,
        User $member,
    ): OrganizationMemberResource {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($currentUser, $organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $member = $this->findMemberInOrganization($organization, $member);

        $this->assertMemberCanBeManaged($member);

        $organization->users()->updateExistingPivot($member->id, [
            'role' => $request->validated('role'),
        ]);

        return new OrganizationMemberResource(
            $this->findMemberInOrganization($organization, $member)
        );
    }

    public function destroy(
        Request $request,
        Organization $organization,
        User $member,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $organization = $this->tenantAccess->findOrganizationForUser($currentUser, $organization);

        $this->tenantAccess->assertCanManageOrganization($organization);

        $member = $this->findMemberInOrganization($organization, $member);

        $this->assertMemberCanBeManaged($member);

        $organization->users()->detach($member->id);

        return response()->noContent();
    }

    private function findMemberInOrganization(Organization $organization, User $member): User
    {
        return $organization
            ->users()
            ->whereKey($member->id)
            ->firstOrFail();
    }

    private function assertMemberCanBeManaged(User $member): void
    {
        if ($member->pivot->role !== 'owner') {
            return;
        }

        throw ValidationException::withMessages([
            'member' => ['Владельца организации нельзя изменить или удалить через этот API.'],
        ]);
    }
}
