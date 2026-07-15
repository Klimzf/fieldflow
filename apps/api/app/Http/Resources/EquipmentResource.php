<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EquipmentResource extends JsonResource
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
            'client_id' => $this->client_id,
            'site_id' => $this->site_id,
            'name' => $this->name,
            'type' => $this->type,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'installed_at' => $this->installed_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
