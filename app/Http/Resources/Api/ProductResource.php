<?php

namespace App\Http\Resources\Api;

use App\Enums\ProductWarrantyBadge;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Produit tel que le consomme l'application mobile.
 *
 * Toutes les URL sont absolues : contrairement au navigateur, l'application
 * n'a pas d'origine à laquelle rattacher un chemin relatif.
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
            'name' => $this->name,
            'description' => $this->description,
            'videoUrl' => $this->video_url,
            'category' => $this->whenLoaded('category', fn (): array => [
                'slug' => $this->category->slug,
                'label' => $this->category->label,
            ]),
            'price' => $this->price,
            'oldPrice' => $this->old_price,
            'badge' => $this->badge,
            'warrantyBadges' => ProductWarrantyBadge::labelsFor($this->warranty_badges),
            'image' => ProductImageService::absoluteUrl($this->image),
            'images' => $this->whenLoaded(
                'images',
                fn (): array => $this->images
                    ->map(fn ($image): ?string => ProductImageService::absoluteUrl($image->url))
                    ->filter()
                    ->values()
                    ->all(),
            ),
            'rating' => (float) $this->rating,
            'reviewsCount' => $this->reviews_count,
            'stock' => $this->stock,
            'inStock' => $this->stock > 0,
            'priceTiers' => $this->whenLoaded(
                'priceTiers',
                fn (): array => $this->priceTiers
                    ->map(fn ($tier): array => [
                        'minQty' => $tier->min_qty,
                        'maxQty' => $tier->max_qty,
                        'price' => $tier->price,
                    ])
                    ->all(),
            ),
            'shareUrl' => route('shop.product', $this->resource),
        ];
    }
}
