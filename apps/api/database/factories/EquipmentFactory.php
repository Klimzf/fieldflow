<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
final class EquipmentFactory extends Factory
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
            'name' => fake()->randomElement([
                'Main Pump',
                'Air Conditioner',
                'Automation Cabinet',
                'Heating Boiler',
                'Server Rack',
            ]),
            'type' => fake()->randomElement([
                'pump',
                'conditioner',
                'cabinet',
                'boiler',
                'server',
            ]),
            'manufacturer' => fake()->company(),
            'model' => fake()->bothify('Model-###'),
            'serial_number' => fake()->bothify('SN-####-????'),
            'installed_at' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forSite(Site $site): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $site->organization_id,
            'client_id' => $site->client_id,
            'site_id' => $site->id,
        ]);
    }
}
