<?php

namespace Tests\Feature\WorkOrders;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_work_orders(): void
    {
        $site = Site::factory()->create();

        $this
            ->getJson("/api/sites/{$site->id}/work-orders")
            ->assertUnauthorized();
    }

    public function test_member_can_list_work_orders_from_own_site(): void
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

        $ownWorkOrder = WorkOrder::factory()
            ->forSite($site)
            ->create([
                'title' => 'Own Work Order',
            ]);

        WorkOrder::factory()->create([
            'title' => 'Foreign Work Order',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}/work-orders")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownWorkOrder->id)
            ->assertJsonPath('data.0.title', 'Own Work Order');
    }

    public function test_non_member_cannot_list_work_orders_from_foreign_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/sites/{$site->id}/work-orders")
            ->assertNotFound();
    }

    public function test_owner_can_create_work_order(): void
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

        $equipment = Equipment::factory()
            ->forSite($site)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/sites/{$site->id}/work-orders", [
                'equipment_id' => $equipment->id,
                'title' => 'Fix broken pump',
                'description' => 'Pump does not start',
                'status' => 'new',
                'priority' => 'high',
                'scheduled_at' => '2026-02-01 10:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Fix broken pump')
            ->assertJsonPath('data.organization_id', $organization->id)
            ->assertJsonPath('data.client_id', $client->id)
            ->assertJsonPath('data.site_id', $site->id)
            ->assertJsonPath('data.equipment_id', $equipment->id);

        $this->assertDatabaseHas('work_orders', [
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => $equipment->id,
            'title' => 'Fix broken pump',
            'status' => 'new',
            'priority' => 'high',
        ]);
    }

    public function test_owner_cannot_create_work_order_with_equipment_from_another_site(): void
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

        $anotherSite = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
        ]);

        $equipment = Equipment::factory()
            ->forSite($anotherSite)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/sites/{$site->id}/work-orders", [
                'equipment_id' => $equipment->id,
                'title' => 'Invalid work order',
                'status' => 'new',
                'priority' => 'medium',
            ])
            ->assertNotFound();
    }

    public function test_technician_cannot_create_work_order(): void
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
            ->postJson("/api/sites/{$site->id}/work-orders", [
                'title' => 'Forbidden work order',
            ])
            ->assertForbidden();
    }

    public function test_member_can_view_work_order_from_own_organization(): void
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

        $workOrder = WorkOrder::factory()
            ->forSite($site)
            ->create([
                'title' => 'Visible Work Order',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $workOrder->id)
            ->assertJsonPath('data.title', 'Visible Work Order');
    }

    public function test_non_member_cannot_view_foreign_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}")
            ->assertNotFound();
    }

    public function test_admin_can_update_work_order(): void
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

        $workOrder = WorkOrder::factory()
            ->forSite($site)
            ->create([
                'title' => 'Old title',
                'status' => 'new',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}", [
                'title' => 'New title',
                'status' => 'in_progress',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('work_orders', [
            'id' => $workOrder->id,
            'title' => 'New title',
            'status' => 'in_progress',
        ]);
    }

    public function test_technician_cannot_update_work_order(): void
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

        $workOrder = WorkOrder::factory()
            ->forSite($site)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}", [
                'title' => 'Forbidden title',
            ])
            ->assertForbidden();
    }
}
