<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Resources\Api\QuoteResource;
use App\Models\Quote;
use App\Services\CartPricer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function __construct(private readonly CartPricer $pricer) {}

    /** Devis établis par le client connecté. */
    public function index(Request $request): AnonymousResourceCollection
    {
        return QuoteResource::collection(
            Quote::where('user_id', $request->user()->id)
                ->with('items')
                ->latest()
                ->paginate(15),
        );
    }

    /**
     * Établit un devis à partir du panier.
     *
     * Rien n'est engagé : aucun stock réservé, aucun code promo consommé.
     * La réponse porte l'URL du PDF, jeton compris.
     */
    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $cart = $this->pricer->price($request->validated('items'));

        if ($cart['lines'] === []) {
            return response()->json([
                'message' => 'Aucun produit disponible dans votre panier.',
            ], 422);
        }

        $quote = DB::transaction(function () use ($request, $cart): Quote {
            $quote = Quote::create([
                'reference' => Quote::generateReference(),
                'token' => Str::random(40),
                'user_id' => $request->user()?->id,
                'customer_name' => $request->validated('customer_name'),
                'customer_phone' => $request->validated('customer_phone'),
                'customer_email' => $request->validated('customer_email'),
                'customer_company' => $request->validated('customer_company'),
                'customer_address' => $request->validated('customer_address'),
                'subtotal' => $cart['subtotal'],
                'shipping' => $cart['shipping'],
                'total' => $cart['total'],
                'note' => $request->validated('note'),
                'valid_until' => now()->addDays((int) config('shop.quotes.validity_days')),
            ]);

            $quote->items()->createMany($cart['lines']);

            return $quote;
        });

        $quote->load('items');

        return response()->json(
            ['data' => (new QuoteResource($quote))->resolve($request)],
            201,
        );
    }
}
