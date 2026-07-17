<?php

declare(strict_types=1);

namespace Tests\Feature\WorkOrders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkOrderUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_work_order_updates(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this
            ->getJson("/api/work-orders/{$workOrder->id}/updates")
            ->assertUnauthorized();
    }

    public function test_member_can_list_work_order_updates_from_own_organization(): void
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

        $update = WorkOrderUpdate::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'user_id' => $user->id,
                'type' => 'comment',
                'message' => 'Test comment',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/updates")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $update->id)
            ->assertJsonPath('data.0.message', 'Test comment')
            ->assertJsonPath('data.0.user.id', $user->id);
    }

    public function test_non_member_cannot_list_foreign_work_order_updates(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/updates")
            ->assertNotFound();
    }

    public function test_member_can_create_comment_for_own_work_order(): void
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
            ->postJson("/api/work-orders/{$workOrder->id}/updates", [
                'message' => 'Checked the equipment on site.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'comment')
            ->assertJsonPath('data.message', 'Checked the equipment on site.')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('work_order_updates', [
            'organization_id' => $organization->id,
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'type' => 'comment',
            'message' => 'Checked the equipment on site.',
        ]);
    }

    public function test_non_member_cannot_create_comment_for_foreign_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/updates", [
                'message' => 'Forbidden comment.',
            ])
            ->assertNotFound();
    }

    public function test_work_order_creation_creates_history_entry(): void
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
            ->postJson("/api/sites/{$site->id}/work-orders", [
                'title' => 'New work order',
                'status' => 'new',
                'priority' => 'medium',
            ])
            ->assertCreated();

        $workOrder = WorkOrder::query()
            ->where('title', 'New work order')
            ->firstOrFail();

        $this->assertDatabaseHas('work_order_updates', [
            'organization_id' => $organization->id,
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'type' => 'created',
            'new_status' => 'new',
        ]);
    }

    public function test_status_change_creates_history_entry(): void
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
                'status' => 'new',
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}", [
                'status' => 'in_progress',
            ])
            ->assertOk();

        $this->assertDatabaseHas('work_order_updates', [
            'organization_id' => $organization->id,
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'type' => 'status_changed',
            'old_status' => 'new',
            'new_status' => 'in_progress',
        ]);
    }
}
