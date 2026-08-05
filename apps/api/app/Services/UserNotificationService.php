<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Models\WorkOrder;
use Illuminate\Support\Str;

final class UserNotificationService
{
    public function notifyWorkOrderCreated(WorkOrder $workOrder, ?User $actor): void
    {
        $this->createForOrganizationMembers(
            workOrder: $workOrder,
            actor: $actor,
            type: 'work_order_created',
            title: 'Новая заявка',
            message: "Создана заявка: {$workOrder->title}",
        );
    }

    public function notifyWorkOrderStatusChanged(
        WorkOrder $workOrder,
        ?User $actor,
        string $oldStatus,
        string $newStatus,
    ): void {
        $this->createForOrganizationMembers(
            workOrder: $workOrder,
            actor: $actor,
            type: 'work_order_status_changed',
            title: 'Статус заявки изменён',
            message: "Заявка \"{$workOrder->title}\" перешла из \"{$oldStatus}\" в \"{$newStatus}\".",
        );
    }

    public function notifyWorkOrderAssigned(
        WorkOrder $workOrder,
        User $assignedUser,
        ?User $actor,
    ): void {
        if ($actor !== null && $assignedUser->id === $actor->id) {
            return;
        }

        $this->createForRecipients(
            workOrder: $workOrder,
            recipientIds: [$assignedUser->id],
            actor: $actor,
            type: 'work_order_assigned',
            title: 'Вас назначили на заявку',
            message: "Вы назначены на заявку: {$workOrder->title}",
        );
    }

    public function notifyWorkOrderCommentCreated(
        WorkOrder $workOrder,
        ?User $actor,
        string $message,
    ): void {
        $preview = Str::limit($message, 120);

        $this->createForOrganizationMembers(
            workOrder: $workOrder,
            actor: $actor,
            type: 'work_order_comment_created',
            title: 'Новый комментарий в заявке',
            message: "Новый комментарий в заявке \"{$workOrder->title}\": {$preview}",
        );
    }

    private function createForOrganizationMembers(
        WorkOrder $workOrder,
        ?User $actor,
        string $type,
        string $title,
        string $message,
    ): void {
        $membersQuery = $workOrder
            ->organization
            ->users()
            ->select('users.id');

        if ($actor !== null) {
            $membersQuery->where('users.id', '<>', $actor->id);
        }

        $this->createForRecipients(
            workOrder: $workOrder,
            recipientIds: $membersQuery->pluck('users.id')->all(),
            actor: $actor,
            type: $type,
            title: $title,
            message: $message,
        );
    }

    /**
     * @param  iterable<int, int>  $recipientIds
     */
    private function createForRecipients(
        WorkOrder $workOrder,
        iterable $recipientIds,
        ?User $actor,
        string $type,
        string $title,
        string $message,
    ): void {
        $createdFor = [];

        foreach ($recipientIds as $recipientId) {
            if (isset($createdFor[$recipientId])) {
                continue;
            }

            $createdFor[$recipientId] = true;

            UserNotification::query()->create([
                'organization_id' => $workOrder->organization_id,
                'user_id' => $recipientId,
                'actor_id' => $actor?->id,
                'work_order_id' => $workOrder->id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'read_at' => null,
            ]);
        }
    }
}
