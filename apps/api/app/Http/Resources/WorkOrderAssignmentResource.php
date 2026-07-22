<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkOrderAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkOrderAssignment
 */
final class WorkOrderAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'work_order_id' => $this->work_order_id,
            'user_id' => $this->user_id,
            'assigned_by_id' => $this->assigned_by_id,
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'assigned_by' => $this->whenLoaded('assignedBy', fn (): ?array => $this->assignedBy === null ? null : [
                'id' => $this->assignedBy->id,
                'name' => $this->assignedBy->name,
                'email' => $this->assignedBy->email,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
