<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Une carte du fil de statuts affiché sur la page d'accueil.
 *
 * @property int $id
 * @property string $title
 * @property string|null $caption
 * @property string $media_type
 * @property string $media_path
 * @property string|null $poster_path
 * @property string|null $link_url
 * @property string|null $link_label
 * @property int $position
 * @property bool $is_active
 */
#[Fillable([
    'title',
    'caption',
    'media_type',
    'media_path',
    'poster_path',
    'link_url',
    'link_label',
    'position',
    'is_active',
])]
class Story extends Model
{
    use Auditable;

    public const TYPE_IMAGE = 'image';

    public const TYPE_VIDEO = 'video';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Story>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isVideo(): bool
    {
        return $this->media_type === self::TYPE_VIDEO;
    }

    /** URL du média principal. */
    public function mediaUrl(): ?string
    {
        return ProductImageService::url($this->media_path);
    }

    /**
     * Vignette affichée dans le fil : le poster pour une vidéo, l'image
     * elle-même sinon.
     */
    public function thumbnailUrl(): ?string
    {
        return ProductImageService::url($this->poster_path ?? $this->media_path);
    }
}
