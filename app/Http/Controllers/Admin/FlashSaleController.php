<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlashSaleController extends Controller
{
    public function index(): Response
    {
        $sales = FlashSale::withCount('products')
            ->orderByDesc('starts_at')
            ->get()
            ->map(fn (FlashSale $sale): array => [
                'id' => $sale->id,
                'title' => $sale->title,
                'startsAt' => $sale->starts_at->toIso8601String(),
                'endsAt' => $sale->ends_at->toIso8601String(),
                'isActive' => $sale->is_active,
                'isRunning' => $sale->isRunning(),
                'productsCount' => $sale->products_count,
            ]);

        return Inertia::render('admin/flash-sales/index', ['sales' => $sales]);
    }

    public function show(FlashSale $flashSale): Response
    {
        $flashSale->load('products.category');

        return Inertia::render('admin/flash-sales/show', [
            'sale' => [
                'id' => $flashSale->id,
                'title' => $flashSale->title,
                'startsAt' => $flashSale->starts_at->toIso8601String(),
                'endsAt' => $flashSale->ends_at->toIso8601String(),
                'isActive' => $flashSale->is_active,
                'isRunning' => $flashSale->isRunning(),
            ],
            'products' => $flashSale->products->map(fn (Product $product): array => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category->label,
                'price' => $product->price,
                'salePrice' => (int) $product->pivot->sale_price,
                'image' => ProductImageService::url($product->image),
            ]),
            // Le sélecteur d'ajout n'a besoin ni des paliers ni des images :
            // juste de quoi identifier un produit et connaître son prix
            // permanent, pour proposer une remise cohérente.
            'catalog' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'price'])
                ->whereNotIn('id', $flashSale->products->pluck('id'))
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sale = FlashSale::create($this->validated($request));

        return to_route('admin.flash-sales.show', $sale)
            ->with('success', "Vente « {$sale->title} » créée. Ajoutez-y des produits.");
    }

    public function update(Request $request, FlashSale $flashSale): RedirectResponse
    {
        $flashSale->update($this->validated($request));

        return back()->with('success', "Vente « {$flashSale->title} » mise à jour.");
    }

    public function destroy(FlashSale $flashSale): RedirectResponse
    {
        $title = $flashSale->title;
        $flashSale->delete();

        return to_route('admin.flash-sales.index')
            ->with('success', "Vente « {$title} » supprimée.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'],
        ], attributes: [
            'title' => 'titre',
            'starts_at' => 'début',
            'ends_at' => 'fin',
        ]);
    }
}
