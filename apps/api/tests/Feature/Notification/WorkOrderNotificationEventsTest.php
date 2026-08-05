<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkOrderNotificationEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_order_creation_notifies_other_organization_members(): void
    {
        [$organization, $client, $site, $actor, $recipient] = $this->createOrganizationContext();

        $response = $this
            ->actingAs($actor, 'sanctum')
            ->postJson("/api/sites/{$site->id}/work-orders", [
                'equipment_id' => null,
                'title' => 'Broken HVAC',
                'description' => 'HVAC is not cooling.',
                'status' => 'new',
                'priority' => 'urgent',
                'scheduled_at' => null,
            ])
            ->assertCreated();

        $workOrderId = $response->json('data.id');

        $this->assertDatabaseHas('user_notifications', [
            'organization_id' => $organization->id,
            'user_id' => $recipient->id,
            'actor_id' => $actor->id,
            'work_order_id' => $workOrderId,
            'type' => 'work_order_created',
        ]);

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $actor->id,
            'work_order_id' => $workOrderId,
            'type' => 'work_order_created',
        ]);
    }

    public function test_work_order_status_change_notifies_other_organization_members(): void
    {
        [$organization, , , $actor, $recipient] = $this->createOrganizationContext();

        $workOrder = WorkOrder::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'new',
        ]);

        $this
            ->actingAs($actor, 'sanctum')
            ->patchJson("/api/work-orders/{$workOrder->id}", [
                'status' => 'in_progress',
            ])
            ->assertOk();

        $this->assertDatabaseHas('user_notifications', [
            'organization_id' => $organization->id,
            'user_id' => $recipient->id,
            'actor_id' => $actor->id,
            'work_order_id' => $workOrder->id,
            'type' => 'work_order_status_changed',
        ]);
    }

    public function test_work_order_assignment_notifies_assigned_user(): void
    {
        [$organization, , , $actor, $recipient] = $this->createOrganizationContext();

        $workOrder = WorkOrder::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this
            ->actingAs($actor, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/assignments", [
                'user_id' => $recipient->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('user_notifications', [
            'organization_id' => $organization->id,
            'user_id' => $recipient->id,
            'actor_id' => $actor->id,
            'work_order_id' => $workOrder->id,
            'type' => 'work_order_assigned',
        ]);
    }

    public function test_work_order_comment_notifies_other_organization_members(): void
    {
        [$organization, , , $actor, $recipient] = $this->createOrganizationContext();

        $workOrder = WorkOrder::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this
            ->actingAs($actor, 'sanctum')
            ->postJson("/api/work-orders/{$workOrder->id}/updates", [
                'message' => 'I started diagnostics.',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('user_notifications', [
            'organization_id' => $organization->id,
            'user_id' => $recipient->id,
            'actor_id' => $actor->id,
            'work_order_id' => $workOrder->id,
            'type' => 'work_order_comment_created',
        ]);
    }

    /**
     * @return array{0: Organization, 1: Client, 2: Site, 3: User, 4: User}
     */
    private function createOrganizationContext(): array
    {
        $organization = Organization::factory()->create();
        $actor = User::factory()->create();
        $recipient = User::factory()->create();

        $organization->users()->attach($actor->id, [
            'role' => 'owner',
        ]);

        $organization->users()->attach($recipient->id, [
            'role' => 'technician',
        ]);

        $client = Client::query()->create([
            'organization_id' => $organization->id,
            'name' => 'ACME Manufacturing',
            'email' => null,
            'phone' => null,
            'address' => null,
            'notes' => null,
        ]);

        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'ACME Main Plant',
            'address' => null,
            'contact_name' => null,
            'contact_phone' => null,
            'notes' => null,
        ]);

        return [$organization, $client, $site, $actor, $recipient];
    }
}
