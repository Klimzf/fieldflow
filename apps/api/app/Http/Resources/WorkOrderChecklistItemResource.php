<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\WorkOrderChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkOrderChecklistItem
 */
final class WorkOrderChecklistItemResource extends JsonResource
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
            'title' => $this->title,
            'is_completed' => $this->is_completed,
            'completed_by_id' => $this->completed_by_id,
            'completed_by' => $this->whenLoaded('completedBy', fn (): ?array => $this->completedBy === null ? null : [
                'id' => $this->completedBy->id,
                'name' => $this->completedBy->name,
                'email' => $this->completedBy->email,
            ]),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'position' => $this->position,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
