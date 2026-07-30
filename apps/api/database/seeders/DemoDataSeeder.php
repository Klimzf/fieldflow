<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderChecklistItem;
use App\Models\WorkOrderUpdate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Demo Owner',
                'password' => Hash::make('Password123!'),
            ],
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('Password123!'),
            ],
        );

        $technician = User::query()->updateOrCreate(
            ['email' => 'tech@example.com'],
            [
                'name' => 'Demo Technician',
                'password' => Hash::make('Password123!'),
            ],
        );

        $organization = Organization::query()->updateOrCreate(
            ['slug' => 'demo-field-service'],
            [
                'name' => 'Demo Field Service',
            ],
        );

        $organization->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner'],
            $admin->id => ['role' => 'admin'],
            $technician->id => ['role' => 'technician'],
        ]);

        $client = Client::query()->create([
            'organization_id' => $organization->id,
            'name' => 'ACME Manufacturing',
            'email' => 'facilities@acme.test',
            'phone' => '+1 555 0100',
            'address' => '100 Industrial Ave',
            'notes' => 'Ключевой demo-клиент для проверки заявок.',
        ]);

        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'name' => 'ACME Main Plant',
            'address' => '100 Industrial Ave, Building A',
            'contact_name' => 'John Facility',
            'contact_phone' => '+1 555 0101',
            'notes' => 'Главный производственный объект.',
        ]);

        $equipment = Equipment::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'name' => 'Main HVAC Unit',
            'type' => 'conditioner',
            'manufacturer' => 'Demo Systems',
            'model' => 'HVAC-500',
            'serial_number' => 'DEMO-HVAC-001',
            'installed_at' => now()->subYears(2)->toDateString(),
            'notes' => 'Основная климатическая установка.',
        ]);

        $urgentWorkOrder = WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => $equipment->id,
            'title' => 'HVAC is not cooling',
            'description' => 'Температура на объекте выше нормы. Нужно проверить систему охлаждения.',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'scheduled_at' => now()->addDay(),
            'completed_at' => null,
        ]);

        $newWorkOrder = WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => $equipment->id,
            'title' => 'Monthly preventive maintenance',
            'description' => 'Плановое обслуживание оборудования.',
            'status' => 'new',
            'priority' => 'medium',
            'scheduled_at' => now()->addDays(3),
            'completed_at' => null,
        ]);

        $completedWorkOrder = WorkOrder::query()->create([
            'organization_id' => $organization->id,
            'client_id' => $client->id,
            'site_id' => $site->id,
            'equipment_id' => $equipment->id,
            'title' => 'Replace air filter',
            'description' => 'Фильтр заменён, оборудование работает нормально.',
            'status' => 'completed',
            'priority' => 'low',
            'scheduled_at' => now()->subDays(2),
            'completed_at' => now()->subDay(),
        ]);

        WorkOrderAssignment::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'user_id' => $technician->id,
            'assigned_by_id' => $admin->id,
        ]);

        WorkOrderAssignment::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $newWorkOrder->id,
            'user_id' => $technician->id,
            'assigned_by_id' => $owner->id,
        ]);

        WorkOrderChecklistItem::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'title' => 'Проверить питание оборудования',
            'is_completed' => true,
            'completed_by_id' => $technician->id,
            'completed_at' => now()->subHours(2),
            'position' => 1,
        ]);

        WorkOrderChecklistItem::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'title' => 'Проверить давление хладагента',
            'is_completed' => false,
            'completed_by_id' => null,
            'completed_at' => null,
            'position' => 2,
        ]);

        WorkOrderUpdate::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'user_id' => $admin->id,
            'type' => 'created',
            'message' => null,
            'old_status' => null,
            'new_status' => 'new',
        ]);

        WorkOrderUpdate::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'user_id' => $admin->id,
            'type' => 'status_changed',
            'message' => null,
            'old_status' => 'new',
            'new_status' => 'in_progress',
        ]);

        WorkOrderUpdate::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $urgentWorkOrder->id,
            'user_id' => $technician->id,
            'type' => 'comment',
            'message' => 'Начал диагностику системы охлаждения.',
            'old_status' => null,
            'new_status' => null,
        ]);

        WorkOrderUpdate::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $newWorkOrder->id,
            'user_id' => $owner->id,
            'type' => 'created',
            'message' => null,
            'old_status' => null,
            'new_status' => 'new',
        ]);

        WorkOrderUpdate::query()->create([
            'organization_id' => $organization->id,
            'work_order_id' => $completedWorkOrder->id,
            'user_id' => $admin->id,
            'type' => 'created',
            'message' => null,
            'old_status' => null,
            'new_status' => 'completed',
        ]);
    }
}
