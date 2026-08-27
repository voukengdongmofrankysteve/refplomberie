<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Vente flash : une sélection de produits à prix réduit, pour un temps limité.
 *
 * @property int $id
 * @property string $title
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property bool $is_active
 */
#[Fillable(['title', 'starts_at', 'ends_at', 'is_active'])]
class FlashSale extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot(['sale_price', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * En cours : activée, et l'heure actuelle tombe dans sa fenêtre.
     *
     * @param  Builder<FlashSale>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $now = Carbon::now();

        $query->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
    }

    public function isRunning(): bool
    {
        return $this->is_active
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }

    /**
     * La vente en cours, celle qui se termine le plus tôt s'il y en avait
     * plusieurs — la vitrine n'en met jamais deux en avant à la fois.
     */
    public static function current(): ?self
    {
        return static::running()->orderBy('ends_at')->first();
    }

    /**
     * La prochaine vente à venir, celle qui démarre le plus tôt.
     *
     * Sans elle, une vente tout juste programmée resterait invisible sur la
     * vitrine jusqu'à l'instant précis de son début — au risque de faire
     * croire, entre-temps, qu'elle n'a jamais été enregistrée.
     */
    public static function next(): ?self
    {
        return static::where('is_active', true)
            ->where('starts_at', '>', Carbon::now())
            ->orderBy('starts_at')
            ->first();
    }

    /**
     * La vente à mettre en avant — en cours, ou à défaut la prochaine à
     * venir —, mise en forme pour le site comme pour l'application mobile.
     *
     * @return array<string, mixed>|null
     */
    public static function currentForStorefront(): ?array
    {
        $sale = static::current();
        $status = 'running';

        if ($sale === null) {
            $sale = static::next();
            $status = 'upcoming';
        }

        if ($sale === null) {
            return null;
        }

        $sale->load(['products' => fn ($query) => $query->active()->with('category')]);

        if ($sale->products->isEmpty()) {
            return null;
        }

        return [
            'id' => $sale->id,
            'title' => $sale->title,
            'status' => $status,
            'startsAt' => $sale->starts_at->toIso8601String(),
            'endsAt' => $sale->ends_at->toIso8601String(),
            'products' => $sale->products->map(fn (Product $product): array => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category->slug,
                'img' => ProductImageService::url($product->image),
                'price' => (int) $product->pivot->sale_price,
                'originalPrice' => $product->price,
                'discount' => (int) round(
                    (1 - $product->pivot->sale_price / $product->price) * 100,
                ),
                'stock' => $product->stock,
                'rating' => (float) $product->rating,
                'reviews' => $product->reviews_count,
            ])->values()->all(),
        ];
    }
}
