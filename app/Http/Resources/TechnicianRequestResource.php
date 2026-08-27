<?php

namespace App\Http\Resources;

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
            'customerName' => $this->customer_name,
            'customerPhone' => $this->customer_phone,
            'address' => $this->address,
            'service' => $this->service,
            'preferredDate' => $this->preferred_date?->format('d/m/Y'),
            'preferredTime' => $this->preferred_time,
            'description' => $this->description,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'adminNote' => $this->admin_note,
            'technicianId' => $this->technician_id,
            'technicianName' => $this->whenLoaded(
                'technician',
                fn () => $this->technician?->name,
            ),
            'accountEmail' => $this->whenLoaded('user', fn () => $this->user?->email),
            'createdAt' => $this->created_at?->format('d/m/Y H:i') ?? '',
        ];
    }
}
