<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductWarrantyBadge;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ListPdfService;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    /**
     * Liste paginée, filtrable par recherche et par catégorie.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $category = $request->string('category')->trim()->value();
        $stock = $request->string('stock')->trim()->value();

        $products = Product::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->whereHas(
                'category',
                fn ($sub) => $sub->where('slug', $category),
            ))
            // Le badge « stock bas » du tableau de bord pointe ici.
            ->when($stock === 'low', fn ($query) => $query->lowStock())
            ->when($stock === 'out', fn ($query) => $query->where('stock', 0))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category->label,
                'price' => $product->price,
                'stock' => $product->stock,
                'stockLevel' => $product->stockLevel(),
                'lowStockThreshold' => $product->low_stock_threshold,
                'badge' => $product->badge,
                'image' => ProductImageService::url($product->image),
                'isActive' => $product->is_active,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'category' => $category,
                'stock' => $stock,
            ],
        ]);
    }

    /**
     * Le catalogue filtré, en PDF — mêmes filtres que la liste à l'écran.
     */
    public function exportPdf(Request $request, ListPdfService $pdf): HttpResponse
    {
        $search = $request->string('search')->trim()->value();
        $category = $request->string('category')->trim()->value();
        $stock = $request->string('stock')->trim()->value();

        $products = Product::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($category !== '', fn ($query) => $query->whereHas(
                'category',
                fn ($sub) => $sub->where('slug', $category),
            ))
            ->when($stock === 'low', fn ($query) => $query->lowStock())
            ->when($stock === 'out', fn ($query) => $query->where('stock', 0))
            ->orderBy('name')
            ->get();

        $document = $pdf->render(
            title: 'Catalogue',
            subtitle: $products->count().' produit'.($products->count() > 1 ? 's' : ''),
            columns: [
                ['label' => 'Produit'],
                ['label' => 'Catégorie'],
                ['label' => 'Prix', 'align' => 'right'],
                ['label' => 'Stock', 'align' => 'right'],
                ['label' => 'Statut'],
            ],
            rows: $products->map(fn (Product $product): array => [
                $product->name,
                $product->category->label,
                number_format($product->price, 0, ',', ' ').' FCFA',
                (string) $product->stock,
                $product->is_active ? 'Actif' : 'Inactif',
            ])->all(),
        );

        return $document->download(ListPdfService::filename('catalogue'));
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/form', [
            'product' => null,
            'categories' => $this->categoryOptions(),
            'warrantyBadges' => ProductWarrantyBadge::options(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $attributes = $request->safe()->except([
                'images',
                'price_tiers',
                'image_file',
                'gallery_files',
            ]);

            if ($request->hasFile('image_file')) {
                $attributes['image'] = $this->images->store($request->file('image_file'));
            }

            $product = Product::create($attributes);
            $this->syncRelations($product, $request);

            return $product;
        });

        return to_route('admin.products.edit', $product)
            ->with('success', "Produit « {$product->name} » créé.");
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'priceTiers']);

        return Inertia::render('admin/products/form', [
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'slug' => $product->slug,
                'name' => $product->name,
                'description' => $product->description,
                'video_url' => $product->video_url,
                'price' => $product->price,
                'old_price' => $product->old_price,
                'badge' => $product->badge,
                'warranty_badges' => $product->warranty_badges ?? [],
                'image' => $product->image,
                'imageUrl' => ProductImageService::url($product->image),
                'stock' => $product->stock,
                'low_stock_threshold' => $product->low_stock_threshold,
                'is_active' => $product->is_active,
                'images' => $product->images
                    ->map(fn ($image): array => [
                        'path' => $image->url,
                        'url' => ProductImageService::url($image->url),
                    ])
                    ->all(),
                'price_tiers' => $product->priceTiers
                    ->map(fn ($tier): array => [
                        'min_qty' => $tier->min_qty,
                        'max_qty' => $tier->max_qty,
                        'price' => $tier->price,
                    ])
                    ->all(),
            ],
            'categories' => $this->categoryOptions(),
            'warrantyBadges' => ProductWarrantyBadge::options(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $attributes = $request->safe()->except([
                'images',
                'price_tiers',
                'image_file',
                'gallery_files',
            ]);

            if ($request->hasFile('image_file')) {
                $previous = $product->image;
                $attributes['image'] = $this->images->store($request->file('image_file'));
                $this->images->delete($previous);
            }

            $product->update($attributes);
            $this->syncRelations($product, $request);
        });

        // On repart de l'URL du produit tel qu'il vient d'être enregistré :
        // un `back()` renverrait vers l'ancien slug, qui ne résout plus rien
        // (404) dès que l'identifiant URL a été modifié.
        return to_route('admin.products.edit', $product)
            ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;

        $this->images->delete($product->image);

        foreach ($product->images as $image) {
            $this->images->delete($image->url);
        }

        $product->delete();

        return to_route('admin.products.index')
            ->with('success', "Produit « {$name} » supprimé.");
    }

    /**
     * Réécrit la galerie et les paliers : la liste envoyée par le formulaire
     * fait foi. Les images retirées sont effacées du disque, les nouvelles sont
     * optimisées et filigranées avant d'être ajoutées.
     */
    private function syncRelations(Product $product, ProductRequest $request): void
    {
        $kept = $request->validated('images', []);

        foreach ($product->images as $existing) {
            if (! in_array($existing->url, $kept, strict: true)) {
                $this->images->delete($existing->url);
            }
        }

        $product->images()->delete();

        $position = 0;

        foreach ($kept as $url) {
            $product->images()->create(['url' => $url, 'position' => $position++]);
        }

        foreach ($request->file('gallery_files', []) as $file) {
            $product->images()->create([
                'url' => $this->images->store($file),
                'position' => $position++,
            ]);
        }

        $product->priceTiers()->delete();

        foreach ($request->validated('price_tiers', []) as $tier) {
            $product->priceTiers()->create($tier);
        }
    }

    /**
     * @return array<int, array{value: int, label: string, slug: string}>
     */
    private function categoryOptions(): array
    {
        return Category::orderBy('position')
            ->get()
            ->map(fn (Category $category): array => [
                'value' => $category->id,
                'label' => $category->label,
                'slug' => $category->slug,
            ])
            ->all();
    }
}
