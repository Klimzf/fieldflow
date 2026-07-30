<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_seeder_creates_demo_users_and_organization_roles(): void
    {
        $this->seed(DemoDataSeeder::class);

        $organization = Organization::query()
            ->where('slug', 'demo-field-service')
            ->firstOrFail();

        $owner = User::query()
            ->where('email', 'owner@example.com')
            ->firstOrFail();

        $admin = User::query()
            ->where('email', 'admin@example.com')
            ->firstOrFail();

        $technician = User::query()
            ->where('email', 'tech@example.com')
            ->firstOrFail();

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $technician->id,
            'role' => 'technician',
        ]);

        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('sites', 1);
        $this->assertDatabaseCount('equipment', 1);
        $this->assertDatabaseCount('work_orders', 3);
        $this->assertDatabaseCount('work_order_assignments', 2);
        $this->assertDatabaseCount('work_order_checklist_items', 2);
    }
}
