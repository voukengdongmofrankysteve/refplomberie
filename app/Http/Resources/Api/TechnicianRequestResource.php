<?php

namespace App\Http\Resources\Api;

use App\Models\TechnicianRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TechnicianRequest
 */
class TechnicianRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'service' => $this->service,
            'address' => $this->address,
            'description' => $this->description,
            'preferredDate' => $this->preferred_date,
            'preferredTime' => $this->preferred_time,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'technicianName' => $this->whenLoaded(
                'technician',
                fn (): ?string => $this->technician?->name,
            ),
            'adminNote' => $this->admin_note,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
