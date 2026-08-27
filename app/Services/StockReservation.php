<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\Product;

/**
 * Réserve, libère et réapplique le stock au fil d'une commande.
 *
 * Sans ce service, deux clients pouvaient commander le même dernier
 * exemplaire d'un produit : rien ne décrémentait le stock à la commande, la
 * seconde vente était acceptée alors qu'il n'y avait plus rien à livrer.
 *
 * Chaque méthode doit être appelée à l'intérieur de la transaction qui écrit
 * la commande. `lockForUpdate()` retient la ligne du produit le temps de la
 * transaction : une seconde commande concurrente sur le même produit attend
 * que la première ait fini de décider, plutôt que de lire un stock déjà
 * promis à quelqu'un d'autre.
 */
class StockReservation
{
    /**
     * Décrémente le stock des lignes d'un panier.
     *
     * @param  array<int, array{product_id: int, product_name: string, quantity: int}>  $lines
     *
     * @throws InsufficientStockException Si une ligne dépasse le stock disponible.
     */
    public function reserve(array $lines): void
    {
        $shortages = [];

        foreach ($lines as $line) {
            $product = Product::whereKey($line['product_id'])->lockForUpdate()->first();

            if ($product === null) {
                continue;
            }

            if ($product->stock < $line['quantity']) {
                $shortages[] = [
                    'name' => $product->name,
                    'requested' => $line['quantity'],
                    'available' => $product->stock,
                ];

                continue;
            }

            $product->decrement('stock', $line['quantity']);
        }

        if ($shortages !== []) {
            throw new InsufficientStockException($shortages);
        }
    }

    /**
     * Rend au stock les articles d'une commande annulée.
     */
    public function release(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            Product::whereKey($item->product_id)->lockForUpdate()->increment('stock', $item->quantity);
        }
    }

    /**
     * Reprend une commande annulée : redécrémente le stock qu'`release()`
     * avait rendu.
     *
     * @throws InsufficientStockException Si le stock a depuis été repris ailleurs.
     */
    public function reapply(Order $order): void
    {
        $order->loadMissing('items');

        $this->reserve(
            $order->items
                ->filter(fn ($item) => $item->product_id !== null)
                ->map(fn ($item): array => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                ])
                ->all(),
        );
    }
}
