<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\TechnicianResource;
use App\Http\Resources\TestimonialResource;
use App\Models\Category;
use App\Models\Faq;
use App\Models\FlashSale;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Story;
use App\Models\Technician;
use App\Models\Testimonial;
use App\Services\ProductImageService;
use App\Services\ProductRecommendations;
use App\Services\PurchaseVerification;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        private readonly Seo $seo,
        private readonly ProductRecommendations $recommendations,
        private readonly PurchaseVerification $purchases,
    ) {}

    /**
     * Page d'accueil : catalogue complet et techniciens disponibles.
     */
    public function home(): Response
    {
        $store = StoreSetting::current();

        $products = Product::query()
            ->active()
            ->with(['category', 'images', 'priceTiers'])
            ->orderBy('id')
            ->get();

        $categories = Category::orderBy('position')
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->slug,
                'label' => $category->label,
            ])
            ->all();

        $faqs = Faq::active()->orderBy('position')->orderBy('id')->get();

        $this->seo
            ->title($store->meta_title ?? $store->name)
            ->description($store->meta_description)
            ->canonical(route('home'))
            ->schema(Seo::organisationSchema())
            ->schema([
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $store->name,
                'url' => url('/'),
            ]);

        // Balisage FAQPage : Google peut alors afficher les questions
        // directement dans les résultats de recherche.
        if ($faqs->isNotEmpty()) {
            $this->seo->schema([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn (Faq $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq->answer,
                    ],
                ])->all(),
            ]);
        }

        return Inertia::render('shop/home', [
            'products' => ProductResource::collection($products)->resolve(),
            // « Tous les produits » n'existe pas en base : c'est un filtre.
            'categories' => [
                ['id' => 'all', 'label' => 'Tous les produits'],
                ...$categories,
            ],
            'technicians' => TechnicianResource::collection(
                Technician::orderByDesc('is_available')->orderBy('name')->get(),
            )->resolve(),
            'services' => config('shop.services'),
            'stories' => StoryResource::collection(
                Story::active()->orderBy('position')->orderByDesc('id')->get(),
            )->resolve(),
            'faqs' => $faqs
                ->map(fn (Faq $faq): array => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'category' => $faq->category,
                ])
                ->all(),
            'flashSale' => FlashSale::currentForStorefront(),
            'testimonials' => TestimonialResource::collection(
                Testimonial::active()->orderBy('position')->orderBy('id')->get(),
            )->resolve(),
        ]);
    }

    /**
     * Fiche produit, ses avis et les produits de la même catégorie.
     */
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['category', 'images', 'priceTiers', 'reviews.user']);

        $related = Product::query()
            ->active()
            ->with(['category', 'images', 'priceTiers'])
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)
            ->get();

        $this->describeProduct($product);

        // Mesuré ici plutôt que dans le middleware : la page vue ne dit pas
        // quel produit a été regardé, et c'est justement ce qu'on veut savoir.
        Analytics::record(
            AnalyticsEvent::ProductView,
            subject: $product,
            label: $product->name,
        );

        $frequentlyBoughtWith = $this->recommendations->frequentlyBoughtWith($product);

        return Inertia::render('shop/product', [
            'product' => (new ProductResource($product))->resolve(),
            'related' => ProductResource::collection($related)->resolve(),
            'frequentlyBoughtWith' => ProductResource::collection($frequentlyBoughtWith)->resolve(),
            'reviews' => ReviewResource::collection($product->reviews)->resolve(),
            'reviewGate' => $this->reviewGate($product),
            // URL absolue : c'est celle que WhatsApp et Facebook iront lire
            // pour construire l'aperçu à partir des balises Open Graph.
            'shareUrl' => route('shop.product', $product),
        ]);
    }

    /**
     * Métadonnées sociales et données structurées de la fiche produit.
     */
    private function describeProduct(Product $product): void
    {
        $store = StoreSetting::current();
        $url = route('shop.product', $product);
        $image = ProductImageService::absoluteUrl($product->image);
        $inStock = $product->stock > 0;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description,
            'sku' => $product->slug,
            'category' => $product->category->label,
            'image' => $product->images->isNotEmpty()
                ? $product->images
                    ->map(fn ($image): ?string => ProductImageService::absoluteUrl($image->url))
                    ->filter()
                    ->values()
                    ->all()
                : array_filter([$image]),
            'brand' => ['@type' => 'Brand', 'name' => $store->name],
            'offers' => [
                '@type' => 'Offer',
                'url' => $url,
                'price' => $product->price,
                'priceCurrency' => 'XAF',
                'availability' => $inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => ['@type' => 'Organization', 'name' => $store->name],
            ],
        ];

        if ($product->reviews_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $product->rating,
                'reviewCount' => $product->reviews_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        $this->seo
            ->title($product->name.' — '.config('app.name'))
            ->description($product->description)
            ->image($image)
            ->type('product')
            ->canonical($url)
            ->productMeta([
                'product:price:amount' => (string) $product->price,
                'product:price:currency' => 'XAF',
                'product:availability' => $inStock ? 'in stock' : 'out of stock',
                'product:condition' => 'new',
                'product:brand' => $store->name,
            ])
            ->schema($schema)
            ->schema([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Accueil',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $product->category->label,
                        'item' => route('home').'#produits',
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $product->name,
                        'item' => $url,
                    ],
                ],
            ]);
    }

    /**
     * Pourquoi le visiteur peut, ou ne peut pas, déposer un avis — la
     * vitrine affiche un message différent selon le cas plutôt qu'un simple
     * booléen qui les confondrait tous.
     *
     * @return 'guest'|'not_purchased'|'already_reviewed'|'can_review'
     */
    private function reviewGate(Product $product): string
    {
        $user = request()->user();

        if ($user === null) {
            return 'guest';
        }

        if (! $this->purchases->hasConfirmedPurchase($user, $product)) {
            return 'not_purchased';
        }

        if ($product->reviews()->where('user_id', $user->id)->exists()) {
            return 'already_reviewed';
        }

        return 'can_review';
    }
}
