<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_notifications(): void
    {
        $this
            ->getJson('/api/notifications')
            ->assertUnauthorized();
    }

    public function test_user_can_view_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        UserNotification::factory()
            ->count(2)
            ->forUser($user)
            ->create();

        UserNotification::factory()
            ->forUser($anotherUser)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_can_mark_own_notification_as_read(): void
    {
        $user = User::factory()->create();

        $notification = UserNotification::factory()
            ->forUser($user)
            ->create([
                'read_at' => null,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_cannot_mark_foreign_notification_as_read(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $notification = UserNotification::factory()
            ->forUser($anotherUser)
            ->create();

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertNotFound();
    }

    public function test_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        UserNotification::factory()
            ->count(3)
            ->forUser($user)
            ->create([
                'read_at' => null,
            ]);

        UserNotification::factory()
            ->forUser($anotherUser)
            ->create([
                'read_at' => null,
            ]);

        $this
            ->actingAs($user, 'sanctum')
            ->patchJson('/api/notifications/read-all')
            ->assertNoContent();

        $this->assertSame(
            0,
            UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        );

        $this->assertSame(
            1,
            UserNotification::query()
                ->where('user_id', $anotherUser->id)
                ->whereNull('read_at')
                ->count(),
        );
    }
}
