<?php

declare(strict_types=1);

namespace Tests\Feature\WorkOrders;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkOrderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_assignments(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this
            ->getJson("/api/work-orders/{$workOrder->id}/assignments")
            ->assertUnauthorized();
    }

    public function test_member_can_list_assignments_from_own_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization);

        $assignment = WorkOrderAssignment::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'user_id' => $assignedUser->id,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/assignments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $assignment->id)
            ->assertJsonPath('data.0.user.id', $assignedUser->id);
    }

    public function test_member_can_list_assignable_users_from_own_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($user, 'technician');
        $organization = $workOrder->organization()->firstOrFail();
        $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/assignable-users")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_non_member_cannot_list_assignments_from_foreign_work_order(): void
    {
        $user = User::factory()->create();
        $workOrder = WorkOrder::factory()->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/work-orders/{$workOrder->id}/assignments")
            ->assertNotFound();
    }

    public function test_owner_can_assign_organization_member_to_work_order(): void
    {
        $owner = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($owner, 'owner');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/assignments", [
                'user_id' => $assignedUser->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.user.id', $assignedUser->id)
            ->assertJsonPath('data.assigned_by.id', $owner->id);

        $this->assertDatabaseHas('work_order_assignments', [
            'organization_id' => $organization->id,
            'work_order_id' => $workOrder->id,
            'user_id' => $assignedUser->id,
            'assigned_by_id' => $owner->id,
        ]);
    }

    public function test_owner_cannot_assign_user_from_another_organization(): void
    {
        $owner = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($owner, 'owner');
        $foreignUser = User::factory()->create();

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/assignments", [
                'user_id' => $foreignUser->id,
            ])
            ->assertNotFound();
    }

    public function test_owner_cannot_assign_same_user_twice(): void
    {
        $owner = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($owner, 'owner');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization, 'technician');

        WorkOrderAssignment::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'user_id' => $assignedUser->id,
            ]);

        $this
            ->actingAs($owner, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/assignments", [
                'user_id' => $assignedUser->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_technician_cannot_assign_user_to_work_order(): void
    {
        $technician = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($technician, 'technician');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization, 'technician');

        $this
            ->actingAs($technician, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/assignments", [
                'user_id' => $assignedUser->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_unassign_user_from_work_order(): void
    {
        $admin = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($admin, 'admin');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization, 'technician');

        $assignment = WorkOrderAssignment::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'user_id' => $assignedUser->id,
            ]);

        $this
            ->actingAs($admin, 'sanctum')
            ->deleteJson("/api/work-order-assignments/{$assignment->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('work_order_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_technician_cannot_unassign_user_from_work_order(): void
    {
        $technician = User::factory()->create();
        $workOrder = $this->createWorkOrderForUser($technician, 'technician');
        $organization = $workOrder->organization()->firstOrFail();
        $assignedUser = $this->createOrganizationMember($organization, 'technician');

        $assignment = WorkOrderAssignment::factory()
            ->forWorkOrder($workOrder)
            ->create([
                'user_id' => $assignedUser->id,
            ]);

        $this
            ->actingAs($technician, 'sanctum')
            ->deleteJson("/api/work-order-assignments/{$assignment->id}")
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
