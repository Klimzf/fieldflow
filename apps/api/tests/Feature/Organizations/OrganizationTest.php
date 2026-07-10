<?php

declare(strict_types=1);

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_organizations(): void
    {
        $this->getJson('/api/organizations')
            ->assertUnauthorized();
    }

    public function test_user_can_create_organization(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/organizations', [
                'name' => 'Acme Service',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Service')
            ->assertJsonPath('data.slug', 'acme-service')
            ->assertJsonPath('data.role', 'owner');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Acme Service',
            'slug' => 'acme-service',
        ]);

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_list_only_own_organizations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownOrganization = Organization::factory()->create([
            'name' => 'Own Organization',
        ]);

        $otherOrganization = Organization::factory()->create([
            'name' => 'Other Organization',
        ]);

        $ownOrganization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $otherOrganization->users()->attach($otherUser->id, [
            'role' => 'owner',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Own Organization');
    }

    public function test_member_can_view_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id)
            ->assertJsonPath('data.role', 'technician');
    }

    public function test_non_member_cannot_view_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}")
            ->assertNotFound();
    }

    public function test_owner_can_update_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Old Name',
        ]);

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/organizations/{$organization->id}", [
                'name' => 'New Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'New Name',
        ]);
    }

    public function test_technician_cannot_update_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/organizations/{$organization->id}", [
                'name' => 'Forbidden Name',
            ])
            ->assertForbidden();
    }
}
