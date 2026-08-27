<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;

/**
 * Un client a-t-il réellement acheté ce produit ?
 *
 * Sert à réserver les avis aux acheteurs, pour que la note d'un produit
 * reflète l'expérience de clients qui l'ont vraiment reçu — pas celle de
 * n'importe quel visiteur connecté.
 */
class PurchaseVerification
{
    /**
     * Vrai si le client a une commande de ce produit qui a dépassé le
     * simple dépôt (« en attente ») sans avoir été annulée.
     */
    public function hasConfirmedPurchase(User $user, Product $product): bool
    {
        return OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNotIn('status', [
                    OrderStatus::Pending->value,
                    OrderStatus::Cancelled->value,
                ]))
            ->exists();
    }
}
