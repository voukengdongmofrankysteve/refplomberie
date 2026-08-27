<?php

namespace App\Services;

use App\Models\FlashSale;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Support\Collection;

/**
 * Chiffrage d'un panier côté serveur.
 *
 * Les prix ne viennent jamais du client : seuls le produit et la quantité sont
 * transmis. Commandes et devis passent tous les deux par ici, ce qui garantit
 * qu'un devis annonce exactement ce que la commande facturera.
 */
class CartPricer
{
    /**
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     * @return array{
     *     lines: array<int, array{product_id: int, product_name: string, unit_price: int, quantity: int, line_total: int}>,
     *     subtotal: int,
     *     shipping: int,
     *     discount: int,
     *     promoCode: string|null,
     *     total: int,
     *     promoError: string|null,
     * }
     */
    public function price(array $items, ?string $promoCode = null): array
    {
        $lines = $this->buildLines($items);
        $subtotal = array_sum(array_column($lines, 'line_total'));

        [$discount, $code, $promoError] = $this->resolvePromo($promoCode, $subtotal);

        // La livraison s'apprécie sur le sous-total remisé : une remise peut
        // donc faire repasser la commande sous le seuil de franchise de port.
        $shipping = $this->shippingFor($subtotal - $discount);

        return [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'promoCode' => $code,
            'total' => $subtotal - $discount + $shipping,
            'promoError' => $promoError,
        ];
    }

    /**
     * Relit les produits en base et applique le palier dégressif.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     * @return array<int, array{product_id: int, product_name: string, unit_price: int, quantity: int, line_total: int}>
     */
    private function buildLines(array $items): array
    {
        /** @var Collection<int|string, int> $quantities */
        $quantities = collect($items)
            ->groupBy('product_id')
            ->map(fn (Collection $group): int => (int) $group->sum('quantity'));

        $products = Product::query()
            ->active()
            ->with('priceTiers')
            ->whereIn('id', $quantities->keys())
            ->get()
            ->keyBy('id');

        $flashPrices = $this->activeFlashSalePrices($quantities->keys()->all());

        $lines = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $unitPrice = min(
                $this->tierPrice($product, $quantity),
                $flashPrices[$product->id] ?? PHP_INT_MAX,
            );

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ];
        }

        return $lines;
    }

    /**
     * @return array{0: int, 1: string|null, 2: string|null}
     */
    private function resolvePromo(?string $code, int $subtotal): array
    {
        if ($code === null || trim($code) === '') {
            return [0, null, null];
        }

        $promo = PromoCode::findByCode($code);

        if ($promo === null) {
            return [0, null, 'Ce code promo est introuvable.'];
        }

        $reason = $promo->rejectionReason($subtotal);

        if ($reason !== null) {
            return [0, null, $reason];
        }

        return [$promo->discountFor($subtotal), $promo->code, null];
    }

    /** Frais de port applicables à un montant donné. */
    public function shippingFor(int $amount): int
    {
        return $amount >= (int) config('shop.shipping.free_from')
            ? 0
            : (int) config('shop.shipping.cost');
    }

    /**
     * Prix de vente flash des produits demandés, s'ils sont concernés par la
     * vente en cours.
     *
     * Relu ici plutôt que fait confiance au panier : sans quoi une vente qui
     * vient de se terminer, ou un article qui n'y a jamais figuré, garderait
     * malgré tout son rabais jusqu'au bout de la commande.
     *
     * @param  array<int, int|string>  $productIds
     * @return array<int, int>
     */
    private function activeFlashSalePrices(array $productIds): array
    {
        $sale = FlashSale::current();

        if ($sale === null) {
            return [];
        }

        return $sale->products()
            ->whereIn('products.id', $productIds)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->id => (int) $product->pivot->sale_price,
            ])
            ->all();
    }

    /** Prix unitaire applicable pour une quantité, d'après les paliers. */
    private function tierPrice(Product $product, int $quantity): int
    {
        $price = $product->price;

        foreach ($product->priceTiers as $tier) {
            if ($quantity >= $tier->min_qty) {
                $price = $tier->price;
            }
        }

        return $price;
    }
}
