<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderAssignment>
 */
final class WorkOrderAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workOrder = WorkOrder::factory()->create();
        $user = User::factory()->create();

        $workOrder->organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        return [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'assigned_by_id' => null,
        ];
    }

    public function forWorkOrder(WorkOrder $workOrder): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
        ]);
    }
}
