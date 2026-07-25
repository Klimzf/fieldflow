<?php

declare(strict_types=1);

namespace Tests\Feature\WorkOrders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkOrderChecklistItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_checklist_items(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this
            ->getJson("/api/work-orders/{$workOrder->id}/checklist-items")
            ->assertUnauthorized();
    }

    public function test_member_can_list_checklist_items_from_own_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        $firstItem = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'title' => 'Check power',
                'position' => 1,
            ]);

        $secondItem = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'title' => 'Check pressure',
                'position' => 2,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/checklist-items")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $firstItem->id)
            ->assertJsonPath('data.0.title', 'Check power')
            ->assertJsonPath('data.1.id', $secondItem->id)
            ->assertJsonPath('data.1.title', 'Check pressure');
    }

    public function test_non_member_cannot_list_checklist_items_from_foreign_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/checklist-items")
            ->assertNotFound();
    }

    public function test_owner_can_create_checklist_item(): void
    {
        $owner = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($owner, 'owner');

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/checklist-items", [
                'title' => 'Inspect filters',
                'position' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Inspect filters')
            ->assertJsonPath('data.position', 10)
            ->assertJsonPath('data.is_completed', false);

        $this->assertDatabaseHas('work_order_checklist_items', [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
            'title' => 'Inspect filters',
            'position' => 10,
        ]);
    }

    public function test_technician_cannot_create_checklist_item(): void
    {
        $technician = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($technician, 'technician');

        $this
            ->actingAs($technician, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/checklist-items", [
                'title' => 'Inspect filters',
            ])
            ->assertForbidden();
    }

    public function test_member_can_complete_checklist_item(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        $item = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'is_completed' => false,
                'completed_by_id' => null,
                'completed_at' => null,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-order-checklist-items/{$item->id}/completion", [
                'is_completed' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_completed', true)
            ->assertJsonPath('data.completed_by.id', $user->id);

        $item->refresh();

        $this->assertTrue($item->is_completed);
        $this->assertSame($user->id, $item->completed_by_id);
        $this->assertNotNull($item->completed_at);
    }

    public function test_member_can_uncomplete_checklist_item(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');

        $item = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->completedBy($user)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-order-checklist-items/{$item->id}/completion", [
                'is_completed' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_completed', false)
            ->assertJsonPath('data.completed_by', null)
            ->assertJsonPath('data.completed_by_id', null);

        $item->refresh();

        $this->assertFalse($item->is_completed);
        $this->assertNull($item->completed_by_id);
        $this->assertNull($item->completed_at);
    }

    public function test_non_member_cannot_complete_foreign_checklist_item(): void
    {
        $user = User::factory()->create();

        $item = WorkOrderChecklistItem::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/work-order-checklist-items/{$item->id}/completion", [
                'is_completed' => true,
            ])
            ->assertNotFound();
    }

    public function test_admin_can_delete_checklist_item(): void
    {
        $admin = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($admin, 'admin');

        $item = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->create();

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/work-order-checklist-items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('work_order_checklist_items', [
            'id' => $item->id,
        ]);
    }

    public function test_technician_cannot_delete_checklist_item(): void
    {
        $technician = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($technician, 'technician');

        $item = WorkOrderChecklistItem::factory()
            ->forWorkOrder($workOrder)
            ->create();

        $this
            ->actingAs($technician, 'sanctum')
            ->deleteJson("/api/work-order-checklist-items/{$item->id}")
            ->assertForbidden();
    }

    private function createWorkOrderForUser(User $user, string $role): WorkOrder
    {
        $organization = Organization::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => $role,
        ]);

        $client = Client::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $site = Site::factory()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
        ]);

        return WorkOrder::factory()
            ->forSite($site)
            ->create();
    }
}
