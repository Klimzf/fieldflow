<?php

declare(strict_types=1);

namespace Tests\Feature\Organizations;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_organization_dashboard(): void
    {
        $organization = Organization::factory()->create();

        $this
            ->getJson("/api/organizations/{$organization->id}/dashboard")
            ->assertUnauthorized();
    }

    public function test_member_can_view_organization_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $firstClient = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $secondClient = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $firstSite = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $firstClient->id,
        ]);

        $secondSite = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $secondClient->id,
        ]);

        Equipment::factory()
            ->forSite($firstSite)
            ->create();

        Equipment::factory()
            ->forSite($secondSite)
            ->create();

        $newWorkOrder = WorkOrder::factory()
            ->forSite($firstSite)
            ->create([
                'status' => 'new',
                'priority' => 'urgent',
            ]);

        WorkOrder::factory()
            ->forSite($firstSite)
            ->create([
                'status' => 'in_progress',
                'priority' => 'medium',
            ]);

        WorkOrder::factory()
            ->forSite($secondSite)
            ->create([
                'status' => 'completed',
                'priority' => 'low',
            ]);

        WorkOrderAssignment::factory()
            ->forWorkOrder($newWorkOrder)
            ->create([
                'user_id' => $user->id,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('data.clients_count', 2)
            ->assertJsonPath('data.sites_count', 2)
            ->assertJsonPath('data.equipment_count', 2)
            ->assertJsonPath('data.work_orders_count', 3)
            ->assertJsonPath('data.work_orders_by_status.new', 1)
            ->assertJsonPath('data.work_orders_by_status.in_progress', 1)
            ->assertJsonPath('data.work_orders_by_status.completed', 1)
            ->assertJsonPath('data.work_orders_by_status.cancelled', 0)
            ->assertJsonPath('data.urgent_work_orders_count', 1)
            ->assertJsonPath('data.assigned_to_me_count', 1)
            ->assertJsonCount(3, 'data.latest_work_orders')
            ->assertJsonCount(1, 'data.assigned_to_me_work_orders');
    }

    public function test_dashboard_does_not_count_foreign_organization_data(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'admin',
        ]);

        Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        Client::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/dashboard")
            ->assertOk()
            ->assertJsonPath('data.clients_count', 1);
    }

    public function test_non_member_cannot_view_foreign_organization_dashboard(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/dashboard")
            ->assertNotFound();
    }
}
