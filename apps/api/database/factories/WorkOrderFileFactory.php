<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrderFile>
 */
final class WorkOrderFileFactory extends Factory
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
            'uploaded_by_id' => $user->id,
            'disk' => 'local',
            'path' => 'work-orders/'.$workOrder->id.'/'.fake()->uuid().'.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ];
    }

    public function forWorkOrder(WorkOrder $workOrder): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $workOrder->organization_id,
            'work_order_id' => $workOrder->id,
        ]);
    }

    public function uploadedBy(User $user): self
    {
        return $this->state(fn (): array => [
            'uploaded_by_id' => $user->id,
        ]);
    }
}
