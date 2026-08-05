<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'actor_id' => $this->actor_id,
            'work_order_id' => $this->work_order_id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'read_at' => $this->read_at?->toIso8601String(),
            'is_read' => $this->read_at !== null,
            'actor' => $this->whenLoaded('actor', fn (): ?array => $this->actor === null ? null : [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ]),
            'work_order' => $this->whenLoaded('workOrder', fn (): ?array => $this->workOrder === null ? null : [
                'id' => $this->workOrder->id,
                'title' => $this->workOrder->title,
                'status' => $this->workOrder->status,
                'priority' => $this->workOrder->priority,
                'client_id' => $this->workOrder->client_id,
                'site_id' => $this->workOrder->site_id,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
