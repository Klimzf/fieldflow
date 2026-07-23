<?php

declare(strict_types=1);

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_organization_members(): void
    {
        $organization = Organization::factory()->create();

        $this
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertUnauthorized();
    }

    public function test_member_can_list_members_from_own_organization(): void
    {
        $organization = Organization::factory()->create();

        $viewer = $this->createOrganizationMember($organization, 'technician');
        $admin = $this->createOrganizationMember($organization, 'admin');

        $this
            ->actingAs($viewer, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $viewer->id,
                'email' => $viewer->email,
                'role' => 'technician',
            ])
            ->assertJsonFragment([
                'id' => $admin->id,
                'email' => $admin->email,
                'role' => 'admin',
            ]);
    }

    public function test_non_member_cannot_list_foreign_organization_members(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertNotFound();
    }

    public function test_owner_can_add_existing_user_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->createOrganizationMember($organization, 'owner');

        $newUser = User::factory()->create([
            'email' => 'new-member@example.com',
        ]);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/members", [
                'email' => 'new-member@example.com',
                'role' => 'technician',
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $newUser->id)
            ->assertJsonPath('data.email', 'new-member@example.com')
            ->assertJsonPath('data.role', 'technician');

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role' => 'technician',
        ]);
    }

    public function test_admin_can_add_existing_user_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrganizationMember($organization, 'admin');

        $newUser = User::factory()->create([
            'email' => 'admin-added@example.com',
        ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/members", [
                'email' => 'admin-added@example.com',
                'role' => 'admin',
            ])
            ->assertCreated()
            ->assertJsonPath('data.id', $newUser->id)
            ->assertJsonPath('data.role', 'admin');
    }

    public function test_technician_cannot_add_member(): void
    {
        $organization = Organization::factory()->create();
        $technician = $this->createOrganizationMember($organization, 'technician');

        $newUser = User::factory()->create([
            'email' => 'forbidden@example.com',
        ]);

        $this
            ->actingAs($technician, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/members", [
                'email' => $newUser->email,
                'role' => 'technician',
            ])
            ->assertForbidden();
    }

    public function test_owner_cannot_add_same_member_twice(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->createOrganizationMember($organization, 'owner');
        $member = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/members", [
                'email' => $member->email,
                'role' => 'technician',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_owner_can_update_member_role(): void
    {
        $organization = Organization::factory()->create();
        $owner = $this->createOrganizationMember($organization, 'owner');
        $member = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($owner, 'sanctum')
            ->patchJson("/api/organizations/{$organization->id}/members/{$member->id}", [
                'role' => 'admin',
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $member->id)
            ->assertJsonPath('data.role', 'admin');

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $member->id,
            'role' => 'admin',
        ]);
    }

    public function test_technician_cannot_update_member_role(): void
    {
        $organization = Organization::factory()->create();
        $technician = $this->createOrganizationMember($organization, 'technician');
        $member = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($technician, 'sanctum')
            ->patchJson("/api/organizations/{$organization->id}/members/{$member->id}", [
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    public function test_owner_role_cannot_be_changed(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrganizationMember($organization, 'admin');
        $owner = $this->createOrganizationMember($organization, 'owner');

        $this
            ->actingAs($admin, 'sanctum')
            ->patchJson("/api/organizations/{$organization->id}/members/{$owner->id}", [
                'role' => 'technician',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member']);
    }

    public function test_admin_can_remove_member(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrganizationMember($organization, 'admin');
        $member = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/organizations/{$organization->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_technician_cannot_remove_member(): void
    {
        $organization = Organization::factory()->create();
        $technician = $this->createOrganizationMember($organization, 'technician');
        $member = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($technician, 'sanctum')
            ->deleteJson("/api/organizations/{$organization->id}/members/{$member->id}")
            ->assertForbidden();
    }

    public function test_owner_cannot_be_removed(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createOrganizationMember($organization, 'admin');
        $owner = $this->createOrganizationMember($organization, 'owner');

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/organizations/{$organization->id}/members/{$owner->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member']);
    }

    private function createOrganizationMember(
        Organization $organization,
        string $role = 'technician',
    ): User {
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => $role,
        ]);

        return $user;
    }
}
