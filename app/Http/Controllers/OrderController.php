<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\PromoCode;
use App\Services\CartPricer;
use App\Services\StockReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartPricer $pricer,
        private readonly StockReservation $stock,
    ) {}

    /**
     * Enregistre la commande passée depuis le panier.
     *
     * Les prix ne viennent jamais du client : ils sont relus en base et le
     * palier dégressif comme le code promo sont appliqués côté serveur.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $cart = $this->pricer->price(
            $request->validated('items'),
            $request->validated('promo_code'),
        );

        if ($cart['lines'] === []) {
            return back()->with('error', 'Aucun produit disponible dans votre panier.');
        }

        try {
            $order = DB::transaction(function () use ($request, $cart): Order {
                // Réservée avant l'écriture de la commande : si le stock
                // manque, rien n'est créé et le tour de la transaction se
                // défait de lui-même.
                $this->stock->reserve($cart['lines']);

                $order = Order::create([
                    'reference' => Order::generateReference(),
                    'user_id' => $request->user()?->id,
                    'customer_name' => $request->validated('customer_name'),
                    'customer_phone' => $request->validated('customer_phone'),
                    'customer_address' => $request->validated('customer_address'),
                    'subtotal' => $cart['subtotal'],
                    'shipping' => $cart['shipping'],
                    'promo_code' => $cart['promoCode'],
                    'discount' => $cart['discount'],
                    'total' => $cart['total'],
                    'note' => $request->validated('note'),
                ]);

                $order->items()->createMany($cart['lines']);

                if ($cart['promoCode'] !== null) {
                    PromoCode::query()->where('code', $cart['promoCode'])->increment('used_count');
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with('success', "Commande {$order->reference} enregistrée.")
            ->with('orderReference', $order->reference);
    }
}
