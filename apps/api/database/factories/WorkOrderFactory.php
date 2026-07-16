<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Site;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
final class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $site = Site::factory()->create();

        return [
            'organization_id' => $site->organization_id,
            'client_id' => $site->client_id,
            'site_id' => $site->id,
            'equipment_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['new', 'in_progress', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'scheduled_at' => fake()->optional()->dateTimeBetween('now', '+14 days'),
            'completed_at' => null,
        ];
    }

    public function forSite(Site $site): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $site->organization_id,
            'client_id' => $site->client_id,
            'site_id' => $site->id,
            'equipment_id' => null,
        ]);
    }

    public function forEquipment(Equipment $equipment): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $equipment->organization_id,
            'client_id' => $equipment->client_id,
            'site_id' => $equipment->site_id,
            'equipment_id' => $equipment->id,
        ]);
    }
}
