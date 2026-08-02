<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_organization_schedule(): void
    {
        $organization = Organization::factory()->create();

        $this
            ->getJson("/api/organizations/{$organization->id}/schedule?start=2026-08-01&end=2026-08-07")
            ->assertUnauthorized();
    }

    public function test_member_can_view_scheduled_work_orders_for_organization_date_range(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        $client = Client::query()->create([
            'organization_id' => $organization->id,
            'name' => 'ACME Manufacturer',
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

        $includedWorkOrder = WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => null,
            'title' => 'Scheduled work order',
            'description' => null,
            'status' => 'new',
            'priority' => 'medium',
            'scheduled_at' => '2026-08-03 10:00:00',
            'completed_at' => null,
        ]);

        WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => null,
            'title' => 'Out of range work order',
            'description' => null,
            'status' => 'new',
            'priority' => 'medium',
            'scheduled_at' => '2026-08-20 10:00:00',
            'completed_at' => null,
        ]);

        WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => null,
            'title' => 'Unscheduled work order',
            'description' => null,
            'status' => 'new',
            'priority' => 'medium',
            'scheduled_at' => null,
            'completed_at' => null,
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/schedule?start=2026-08-01&end=2026-08-07")
            ->assertOk();
    }

    public function test_schedule_requires_valid_date_range(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user->id, [
            'role' => 'owner',
        ]);

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/organizations/{$organization->id}/schedule?start=2026-08-07&end=2026-08-01")
            ->assertUnprocessable();
    }
}
