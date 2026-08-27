<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Suggestions fondées sur ce que les clients ont réellement acheté ensemble.
 *
 * Contrairement aux « produits similaires » — qui ne regardent que la
 * catégorie — celles-ci lisent l'historique des commandes : deux produits de
 * rayons différents (un tuyau et son collier de serrage) peuvent très bien se
 * retrouver ici alors qu'ils ne se ressembleraient jamais par la catégorie.
 *
 * Calculées à la demande, comme le reste des statistiques du site : à
 * l'échelle d'une boutique, une requête ponctuelle reste rapide et toujours
 * juste, sans le coût de tenir une table à jour.
 */
class ProductRecommendations
{
    /**
     * Produits actifs le plus souvent retrouvés dans la même commande que
     * celui-ci, du plus fréquent au moins fréquent.
     *
     * @return Collection<int, Product>
     */
    public function frequentlyBoughtWith(Product $product, int $limit = 4): Collection
    {
        // Une commande annulée n'a jamais vraiment eu lieu : elle ne doit pas
        // faire croire à une association d'achat qui n'a jamais existé.
        $ranked = DB::table('order_items as origin')
            ->join('order_items as paired', 'paired.order_id', '=', 'origin.order_id')
            ->join('orders', 'orders.id', '=', 'origin.order_id')
            ->where('origin.product_id', $product->id)
            ->where('paired.product_id', '!=', $product->id)
            ->whereNotNull('paired.product_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->groupBy('paired.product_id')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit($limit)
            ->pluck(DB::raw('COUNT(*) as co_occurrences'), 'paired.product_id');

        if ($ranked->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->active()
            ->with(['category', 'priceTiers'])
            ->whereIn('id', $ranked->keys())
            ->get()
            ->keyBy('id');

        // L'ordre de la requête SQL — du plus co-acheté au moins — se perd
        // dans whereIn : on le restitue ici plutôt que de le laisser au hasard
        // de l'ordre des identifiants.
        return $ranked->keys()
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }
}
