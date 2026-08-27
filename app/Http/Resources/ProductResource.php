<?php

namespace App\Http\Resources;

use App\Enums\ProductWarrantyBadge;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forme la charge utile attendue par le type TypeScript `Product`.
 *
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category' => $this->category->slug,
            'categoryLabel' => $this->category->label,
            'name' => $this->name,
            'desc' => $this->description,
            'videoUrl' => $this->video_url,
            'price' => $this->price,
            'oldPrice' => $this->old_price,
            'badge' => $this->badge,
            'warrantyBadges' => ProductWarrantyBadge::labelsFor($this->warranty_badges),
            'img' => ProductImageService::url($this->image),
            'images' => $this->images
                ->map(fn ($image): ?string => ProductImageService::url($image->url))
                ->filter()
                ->values()
                ->all(),
            'rating' => (float) $this->rating,
            'reviews' => $this->reviews_count,
            'stock' => $this->stock,
            'priceTiers' => $this->priceTiers
                ->map(fn ($tier): array => [
                    'minQty' => $tier->min_qty,
                    'maxQty' => $tier->max_qty,
                    'price' => $tier->price,
                ])
                ->all(),
        ];
    }
}
