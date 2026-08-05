<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
final class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    public function definition(): array
    {
        $workOrder = WorkOrder::factory()->create();
        $user = User::factory()->create();

        $workOrder->organization->users()->attach($user->id, [
            'role' => 'technician',
        ]);

        return [
            'organization_id' => $workOrder->organization_id,
            'user_id' => $user->id,
            'actor_id' => null,
            'work_order_id' => $workOrder->id,
            'type' => 'work_order_created',
            'title' => 'Новая заявка',
            'message' => "Создана заявка: {$workOrder->title}",
            'read_at' => null,
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }

    public function forOrganization(Organization $organization): self
    {
        return $this->state(fn (): array => [
            'organization_id' => $organization->id,
        ]);
    }

    public function read(): self
    {
        return $this->state(fn (): array => [
            'read_at' => now(),
        ]);
    }
}
