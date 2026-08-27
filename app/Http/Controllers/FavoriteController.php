<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Ajoute ou retire un produit des favoris du client connecté.
     */
    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $result = $request->user()->favorites()->toggle($product->id);
        $added = in_array($product->id, $result['attached'], strict: true);

        // Seul l'ajout est mesuré : le retrait d'un favori ne dit rien de
        // l'intérêt pour le produit, il dit que le client a changé d'avis.
        if ($added) {
            Analytics::record(
                AnalyticsEvent::FavoriteAdded,
                subject: $product,
                label: $product->name,
            );
        }

        $message = $added
            ? "« {$product->name} » ajouté à vos favoris."
            : "« {$product->name} » retiré de vos favoris.";

        return back(fallback: route('home'))->with('success', $message);
    }
}
