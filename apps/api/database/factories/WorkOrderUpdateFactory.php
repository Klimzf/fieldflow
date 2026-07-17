<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderUpdate>
 */
final class WorkOrderUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workOrder = WorkOrder::factory()->create();

        return [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
            'user_id' => User::factory(),
            'type' => 'comment',
            'message' => fake()->sentence(),
            'old_status' => null,
            'new_status' => null,
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
