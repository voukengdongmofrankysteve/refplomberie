<?php

namespace App\Http\Controllers\Api;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Http\Resources\Api\StoryResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\TechnicianResource;
use App\Models\Category;
use App\Models\Faq;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Story;
use App\Models\Technician;
use App\Services\ProductRecommendations;
use App\Services\PurchaseVerification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catalogue servi à l'application mobile.
 *
 * Le back-office reste sur le web : cette API n'expose que ce dont un client
 * a besoin, en lecture, sans aucune route d'administration.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductRecommendations $recommendations,
        private readonly PurchaseVerification $purchases,
    ) {}

    /**
     * Charge utile de démarrage de l'application.
     *
     * Un seul appel au lancement : coordonnées, catégories, services et
     * réglages de livraison. Sur un réseau mobile, une requête épargnée
     * compte plus qu'un octet économisé.
     */
    public function bootstrap(): JsonResponse
    {
        $store = StoreSetting::current();

        return response()->json([
            'store' => [
                ...$store->toSharedArray(),
                'latitude' => $store->latitude,
                'longitude' => $store->longitude,
            ],
            'categories' => Category::orderBy('position')
                ->get()
                ->map(fn (Category $category): array => [
                    'slug' => $category->slug,
                    'label' => $category->label,
                ])
                ->all(),
            'services' => config('shop.services'),
            'quoteValidityDays' => (int) config('shop.quotes.validity_days'),
            'flashSale' => FlashSale::currentForStorefront(),
        ]);
    }

    /**
     * Catalogue paginé, filtrable et triable.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('categorie', ''));

        $products = Product::query()
            ->active()
            ->with(['category', 'priceTiers'])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->when($category !== '' && $category !== 'all', fn ($query) => $query->whereHas(
                'category',
                fn ($sub) => $sub->where('slug', $category),
            ))
            ->when($request->boolean('promo'), fn ($query) => $query->whereNotNull('old_price'))
            ->tap(fn ($query) => $this->applySort($query, (string) $request->query('tri', '')))
            ->paginate(perPage: min((int) $request->query('par_page', 20), 50));

        return ProductResource::collection($products);
    }

    /**
     * Fiche produit : galerie, paliers, avis et suggestions.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'images', 'priceTiers', 'reviews.user']);

        // L'application mesure la consultation au même titre que le site :
        // les deux alimentent le même classement des produits les plus vus.
        Analytics::record(
            AnalyticsEvent::ProductView,
            subject: $product,
            label: $product->name,
            path: '/produit/'.$product->slug,
        );

        $related = Product::query()
            ->active()
            ->with(['category', 'priceTiers'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(6)
            ->get();

        $frequentlyBoughtWith = $this->recommendations->frequentlyBoughtWith($product);

        return response()->json([
            'data' => (new ProductResource($product))->resolve($request),
            'related' => ProductResource::collection($related)->resolve($request),
            'frequentlyBoughtWith' => ProductResource::collection($frequentlyBoughtWith)->resolve($request),
            'reviews' => ReviewResource::collection($product->reviews)->resolve($request),
            // Un client ne dépose qu'un avis par produit, seulement connecté,
            // et seulement s'il l'a réellement acheté.
            'canReview' => $request->user() !== null
                && $this->purchases->hasConfirmedPurchase($request->user(), $product)
                && ! $product->reviews()->where('user_id', $request->user()->id)->exists(),
        ]);
    }

    /** Recherche instantanée du champ de l'application. */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $products = Product::query()
            ->active()
            ->with(['category', 'priceTiers'])
            ->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('description', 'like', $like))
            // Une correspondance sur le nom prime sur une description.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderBy('name')
            ->take(15)
            ->get();

        Analytics::record(
            AnalyticsEvent::Search,
            label: $term,
            value: $products->count(),
        );

        return response()->json([
            'data' => ProductResource::collection($products)->resolve($request),
        ]);
    }

    /** Statuts façon « stories » de la page d'accueil. */
    public function stories(Request $request): JsonResponse
    {
        return response()->json([
            'data' => StoryResource::collection(
                Story::active()->orderBy('position')->orderByDesc('id')->get(),
            )->resolve($request),
        ]);
    }

    /** Équipe de techniciens, les disponibles d'abord. */
    public function technicians(Request $request): JsonResponse
    {
        return response()->json([
            'data' => TechnicianResource::collection(
                Technician::orderByDesc('is_available')->orderBy('name')->get(),
            )->resolve($request),
        ]);
    }

    /** Questions fréquentes, publiées et classées. */
    public function faqs(): JsonResponse
    {
        return response()->json([
            'data' => Faq::active()
                ->orderBy('position')
                ->orderBy('id')
                ->get(['id', 'question', 'answer', 'category']),
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'prix-asc' => $query->orderBy('price'),
            'prix-desc' => $query->orderByDesc('price'),
            'note' => $query->orderByDesc('rating'),
            'recent' => $query->orderByDesc('id'),
            default => $query->orderBy('id'),
        };
    }
}
