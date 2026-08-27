<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\Api\OrderResource;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\StoreSetting;
use App\Services\CartPricer;
use App\Services\StockReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartPricer $pricer,
        private readonly StockReservation $stock,
    ) {}

    /** Commandes du client connecté, de la plus récente à la plus ancienne. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            $request->user()->orders()->withCount('items')->paginate(15),
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        // Une commande ne se consulte que par son propriétaire.
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load('items');

        return response()->json(['data' => (new OrderResource($order))->resolve($request)]);
    }

    /**
     * Enregistre une commande passée depuis l'application.
     *
     * Les prix ne viennent jamais du client : ils sont relus en base, palier
     * dégressif et code promo appliqués côté serveur. La réponse porte le
     * message WhatsApp tout prêt, que l'application ouvre ensuite.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $cart = $this->pricer->price(
            $request->validated('items'),
            $request->validated('promo_code'),
        );

        if ($cart['lines'] === []) {
            return response()->json([
                'message' => 'Aucun produit disponible dans votre panier.',
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $cart): Order {
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
            return response()->json([
                'message' => $e->getMessage(),
                'shortages' => $e->shortages,
            ], 422);
        }

        $order->load('items');

        return response()->json([
            'data' => (new OrderResource($order))->resolve($request),
            'whatsApp' => [
                'phone' => StoreSetting::current()->whatsapp,
                'message' => $this->confirmationMessage($order),
            ],
            'promoRejected' => $cart['promoError'],
        ], 201);
    }

    /**
     * Récapitulatif envoyé à la boutique sur WhatsApp après enregistrement.
     *
     * Construit ici plutôt que dans l'application : le serveur est seul à
     * connaître les montants réellement retenus.
     */
    private function confirmationMessage(Order $order): string
    {
        $store = StoreSetting::current();
        $money = fn (int $amount): string => number_format($amount, 0, ',', ' ').' FCFA';

        $lines = [
            "🛒 *Nouvelle commande — {$store->name}*",
            '',
            "Référence : *{$order->reference}*",
            "Client : {$order->customer_name}",
            "Téléphone : {$order->customer_phone}",
        ];

        if ($order->customer_address) {
            $lines[] = "Adresse : {$order->customer_address}";
        }

        if ($order->note) {
            $lines[] = "Note : {$order->note}";
        }

        $lines[] = '';

        foreach ($order->items as $item) {
            $lines[] = "• {$item->product_name} x{$item->quantity} — ".$money($item->line_total);
        }

        $lines[] = '';

        if ($order->discount > 0) {
            $lines[] = "Remise ({$order->promo_code}) : -".$money($order->discount);
        }

        $lines[] = 'Livraison : '.($order->shipping === 0 ? 'Gratuite' : $money($order->shipping));
        $lines[] = '*Total : '.$money($order->total).'*';
        $lines[] = '';
        $lines[] = 'Merci de confirmer ma commande.';
        $lines[] = '';
        $lines[] = '🔗 '.url('/');

        return implode("\n", $lines);
    }
}
