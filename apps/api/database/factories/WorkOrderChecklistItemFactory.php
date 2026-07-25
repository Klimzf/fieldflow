<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderChecklistItem>
 */
final class WorkOrderChecklistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workOrder = WorkOrder::factory()->create();

        return [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
            'title' => fake()->sentence(4),
            'is_completed' => false,
            'completed_by_id' => null,
            'completed_at' => null,
            'position' => fake()->numberBetween(1, 10),
        ];
    }

    public function forWorkOrder(WorkOrder $workOrder): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
        ]);
    }

    public function completedBy(User $user): self
    {
        return $this->state(fn (): array => [
            'is_completed' => true,
            'completed_by_id' => $user->id,
            'completed_at' => now(),
        ]);
    }
}
