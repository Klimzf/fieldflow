<?php

declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_sites(): void
    {
        $client = Client::factory()->create();

        $this
            ->getJson("/api/clients/{$client->id}/sites")
            ->assertUnauthorized();
    }

    public function test_member_can_list_sites_from_own_client(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $otherClient = Client::factory()->create([
            'organization_id' => $otherOrganization->id,
        ]);

        $ownSite = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'Own Site',
        ]);

        Site::factory()->create([
            'organization_id' => $otherOrganization->id,
            'client_id' => $otherClient->id,
            'name' => 'Other Site',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/clients/{$client->id}/sites")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownSite->id)
            ->assertJsonPath('data.0.name', 'Own Site');
    }

    public function test_non_member_cannot_list_sites_from_foreign_client(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/clients/{$client->id}/sites")
            ->assertNotFound();
    }

    public function test_owner_can_create_site(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/clients/{$client->id}/sites", [
                'name' => 'Main Office',
                'address' => 'Test address',
                'contact_name' => 'Ivan Ivanov',
                'contact_phone' => '+79990000000',
                'notes' => 'Important site',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main Office')
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.client_id', $client->id);

        $this->assertDatabaseHas('sites', [
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'Main Office',
        ]);
    }

    public function test_technician_cannot_create_site(): void
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
            ->postJson("/api/clients/{$client->id}/sites", [
                'name' => 'Forbidden Site',
            ])
            ->assertForbidden();
    }

    public function test_member_can_view_site_from_own_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $site = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'Visible Site',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $site->id)
            ->assertJsonPath('data.name', 'Visible Site');
    }

    public function test_non_member_cannot_view_foreign_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}")
            ->assertNotFound();
    }

    public function test_admin_can_update_site(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'admin',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $site = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'Old Site Name',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/sites/{$site->id}", [
                'name' => 'New Site Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Site Name');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'New Site Name',
        ]);
    }

    public function test_technician_cannot_update_site(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $site = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/sites/{$site->id}", [
                'name' => 'Forbidden Name',
            ])
            ->assertForbidden();
    }
}
