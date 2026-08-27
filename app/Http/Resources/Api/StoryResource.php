<?php

namespace App\Http\Resources\Api;

use App\Models\Story;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Statut tel que le consomme l'application mobile.
 *
 * Les URL de média sont absolues, contrairement à la ressource web : un
 * navigateur résout un chemin relatif contre l'origine de la page, une
 * application n'a aucune origine et n'afficherait rien.
 *
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
            'mediaUrl' => ProductImageService::absoluteUrl($this->media_path),
            'thumbnailUrl' => ProductImageService::absoluteUrl(
                $this->poster_path ?? $this->media_path,
            ),
            'linkUrl' => $this->link_url,
            'linkLabel' => $this->link_label,
            // Horodatage : l'application affiche « il y a 4 h », comme
            // WhatsApp le fait sous le nom de l'auteur.
            'publishedAt' => $this->created_at?->toIso8601String(),
            'position' => $this->position,
        ];
    }
}
