<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $slug
 * @property string $name
 * @property string $description
 * @property string|null $video_url
 * @property int $price
 * @property int|null $old_price
 * @property string|null $badge
 * @property list<string>|null $warranty_badges
 * @property string $image
 * @property float $rating
 * @property int $reviews_count
 * @property int $stock
 * @property int $low_stock_threshold
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'category_id',
    'slug',
    'name',
    'description',
    'video_url',
    'price',
    'old_price',
    'badge',
    'warranty_badges',
    'image',
    'rating',
    'reviews_count',
    'stock',
    'low_stock_threshold',
    'is_active',
])]
class Product extends Model
{
    use Auditable;

    /**
     * Les fiches produit sont adressées par slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'is_active' => 'boolean',
            'warranty_badges' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * @return HasMany<PriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('min_qty');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Produits à réapprovisionner : stock au niveau du seuil ou en dessous.
     * Un seuil à zéro sort de la surveillance.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('low_stock_threshold', '>', 0)
            ->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    /** Niveau de stock, pour la pastille du back-office. */
    public function stockLevel(): string
    {
        if ($this->stock === 0) {
            return 'out';
        }

        if ($this->low_stock_threshold > 0 && $this->stock <= $this->low_stock_threshold) {
            return 'low';
        }

        return 'ok';
    }

    /**
     * Recalcule la note et le nombre d'avis à partir des avis publiés.
     */
    public function refreshRating(): void
    {
        $this->rating = round((float) $this->reviews()->avg('rating'), 1);
        $this->reviews_count = $this->reviews()->count();
        $this->save();
    }
}
