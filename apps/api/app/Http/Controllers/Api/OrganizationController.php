<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationAccessService $organizationAccess,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = $request
            ->user()
            ->organizations()
            ->orderBy('name')
            ->get();

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = DB::transaction(function () use ($request): Organization {
            $organization = Organization::query()->create($request->validated());

            $organization->users()->attach($request->user()->id, [
                'role' => 'owner',
            ]);

            return $request
                ->user()
                ->organizations()
                ->whereKey($organization->id)
                ->firstOrFail();
        });

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Organization $organization): OrganizationResource
    {
        /** @var User $user */
        $user = $request->user();

        return new OrganizationResource(
            $this->organizationAccess->findForUser($user, $organization)
        );
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
    ): OrganizationResource {
        /** @var User $user */
        $user = $request->user();

        $organization = $this->organizationAccess->findForUser($user, $organization);

        $this->organizationAccess->assertCanManage($organization);

        $organization->update($request->validated());

        return new OrganizationResource($organization->refresh());
    }
}
