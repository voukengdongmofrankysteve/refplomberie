<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Recherche instantanée du bandeau de navigation.
     *
     * Interrogée à chaque frappe : la réponse est volontairement minuscule
     * (dix produits, quelques champs) et sert uniquement à afficher la liste
     * déroulante. Elle est disponible depuis n'importe quelle page, y compris
     * une fiche produit où le catalogue complet n'est pas chargé.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'categories' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $products = Product::query()
            ->active()
            ->with('category')
            ->where(fn ($query) => $query
                ->where('name', 'like', $like)
                ->orWhere('description', 'like', $like))
            // Une correspondance sur le nom prime sur une correspondance
            // trouvée au fond d'une description.
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderBy('name')
            ->take(10)
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'category' => $product->category->label,
                'price' => $product->price,
                'img' => ProductImageService::url($product->image),
                'stock' => $product->stock,
            ])
            ->all();

        $categories = Category::query()
            ->where('label', 'like', $like)
            ->orderBy('position')
            ->take(4)
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->slug,
                'label' => $category->label,
            ])
            ->all();

        // Le nombre de résultats est enregistré avec le terme : une
        // recherche qui ne trouve rien signale un produit à référencer.
        Analytics::record(
            AnalyticsEvent::Search,
            label: $term,
            value: count($products),
        );

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
