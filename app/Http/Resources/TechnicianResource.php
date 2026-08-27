<?php

namespace App\Http\Resources;

use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Technician
 */
class TechnicianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialty' => $this->specialty,
            'experience' => $this->experience,
            'rating' => (float) $this->rating,
            'jobs' => $this->jobs_count,
            'img' => $this->photo,
            'available' => $this->is_available,
        ];
    }
}
