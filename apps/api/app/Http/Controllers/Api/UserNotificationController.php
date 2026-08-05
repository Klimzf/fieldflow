<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserNotificationResource;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class UserNotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = UserNotification::query()
            ->where('user_id', $user->id)
            ->with(['actor', 'workOrder'])
            ->latest()
            ->paginate(20);

        return UserNotificationResource::collection($notifications);
    }

    public function markAsRead(Request $request, UserNotification $userNotification): UserNotificationResource
    {
        /** @var User $user */
        $user = $request->user();

        $notification = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereKey($userNotification->id)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return new UserNotificationResource(
            $notification->refresh()->load(['actor', 'workOrder']),
        );
    }

    public function markAllAsRead(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return response()->noContent();
    }
}
