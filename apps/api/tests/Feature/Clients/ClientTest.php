<?php

declare(strict_types=1);

namespace Tests\Feature\Clients;

use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_clients(): void
    {
        $organization = Organization::factory()->create();

        $this
            ->getJson("/api/organizations/{$organization->id}/clients")
            ->assertUnauthorized();
    }

    public function test_member_can_list_clients_frow_own_organization(): void
    {
        $user = User::factory()->create();

        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $ownClient = Client::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Own Client',
        ]);

        Client::factory()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other Client',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/clients")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownClient->id)
            ->assertJsonPath('data.0.name', 'Own Client');
    }

    public function test_non_member_cannot_list_clients_from_foreign_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/clients")
            ->assertNotFound();
    }

    public function test_owner_can_create_client(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/clients", [
                'name' => 'Acme Client',
                'email' => 'CLIENT@EXAMPLE.COM',
                'phone' => '+79990000000',
                'address' => 'Test address',
                'notes' => 'Important client',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Acme Client')
            ->assertJsonPath('data.email', 'client@example.com')
            ->assertJsonPath('data.organization_id', $organization->id);

        $this->assertDatabaseHas('clients', [
            'organization_id' => $organization->id,
            'name' => 'Acme Client',
            'email' => 'client@example.com',
        ]);
    }

    public function test_technician_cannot_create_client(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/organizations/{$organization->id}/clients", [
                'name' => 'Forbidden Client',
            ])
            ->assertForbidden();
    }

    public function test_member_can_view_client_from_own_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Visible Client',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.name', 'Visible Client');
    }

    public function test_non_member_cannot_view_foreign_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/clients/{$client->id}")
            ->assertNotFound();
    }

    public function test_admin_can_update_client(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'admin',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
            'name' => 'Old Client Name',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/clients/{$client->id}", [
                'name' => 'New Client Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Client Name');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'New Client Name',
        ]);
    }

    public function test_technician_cannot_update_client(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/clients/{$client->id}", [
                'name' => 'Forbidden Name',
            ])
            ->assertForbidden();
    }
}
