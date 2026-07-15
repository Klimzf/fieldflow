<?php

declare(strict_types=1);

namespace Tests\Feature\Equipment;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_equipment(): void
    {
        $site = Site::factory()->create();

        $this
            ->getJson("/api/sites/{$site->id}/equipment")
            ->assertUnauthorized();
    }

    public function test_member_can_list_equipment_from_own_site(): void
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

        $ownEquipment = Equipment::factory()
            ->forSite($site)
            ->create([
                'name' => 'Own Equipment',
            ]);

        Equipment::factory()->create([
            'name' => 'Foreign Equipment',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}/equipment")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownEquipment->id)
            ->assertJsonPath('data.0.name', 'Own Equipment');
    }

    public function test_non_member_cannot_list_equipment_from_foreign_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}/equipment")
            ->assertNotFound();
    }

    public function test_owner_can_create_equipment(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
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
            ->postJson("/api/sites/{$site->id}/equipment", [
                'name' => 'Main Pump',
                'type' => 'pump',
                'manufacturer' => 'Test Manufacturer',
                'model' => 'P-100',
                'serial_number' => 'SN-001',
                'installed_at' => '2026-01-15',
                'notes' => 'Important equipment',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Main Pump')
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.site_id', $site->id);

        $this->assertDatabaseHas('equipment', [
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'name' => 'Main Pump',
        ]);
    }

    public function test_technician_cannot_create_equipment(): void
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
            ->postJson("/api/sites/{$site->id}/equipment", [
                'name' => 'Forbidden Equipment',
            ])
            ->assertForbidden();
    }

    public function test_member_can_view_equipment_from_own_organization(): void
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

        $equipment = Equipment::factory()
            ->forSite($site)
            ->create([
                'name' => 'Visible Equipment',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $equipment->id)
            ->assertJsonPath('data.name', 'Visible Equipment');
    }

    public function test_non_member_cannot_view_foreign_equipment(): void
    {
        $user = User::factory()->create();
        $equipment = Equipment::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/equipment/{$equipment->id}")
            ->assertNotFound();
    }

    public function test_admin_can_update_equipment(): void
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
        ]);

        $equipment = Equipment::factory()
            ->forSite($site)
            ->create([
                'name' => 'Old Equipment Name',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/equipment/{$equipment->id}", [
                'name' => 'New Equipment Name',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Equipment Name');

        $this->assertDatabaseHas('equipment', [
            'id' => $equipment->id,
            'name' => 'New Equipment Name',
        ]);
    }

    public function test_technician_cannot_update_equipment(): void
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

        $equipment = Equipment::factory()
            ->forSite($site)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/equipment/{$equipment->id}", [
                'name' => 'Forbidden Equipment Name',
            ])
            ->assertForbidden();
    }
}
