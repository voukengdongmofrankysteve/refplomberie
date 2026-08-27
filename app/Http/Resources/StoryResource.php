<?php

namespace App\Http\Resources;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Story
 */
class StoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'caption' => $this->caption,
            'type' => $this->media_type,
            'mediaUrl' => $this->mediaUrl(),
            'thumbnailUrl' => $this->thumbnailUrl(),
            'linkUrl' => $this->link_url,
            'linkLabel' => $this->link_label,
            // Horodatage : l'application affiche « il y a 4 h », comme
            // WhatsApp le fait sous le nom de l'auteur.
            'publishedAt' => $this->created_at?->toIso8601String(),
            'position' => $this->position,
            'isActive' => $this->is_active,
        ];
    }
}
