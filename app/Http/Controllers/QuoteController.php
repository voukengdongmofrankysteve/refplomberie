<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Quote;
use App\Services\CartPricer;
use App\Services\QuotePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function __construct(
        private readonly CartPricer $pricer,
        private readonly QuotePdfService $pdf,
    ) {}

    /**
     * Établit un devis à partir du panier.
     *
     * Un devis n'engage rien : aucun stock n'est réservé, aucun code promo
     * n'est consommé. C'est le document que réclament les clients
     * professionnels et les appels d'offres, là où WhatsApp ne suffit pas.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $cart = $this->pricer->price($request->validated('items'));

        if ($cart['lines'] === []) {
            return back()->with('error', 'Aucun produit disponible dans votre panier.');
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
                'valid_until' => now()->addDays(
                    (int) config('shop.quotes.validity_days'),
                ),
            ]);

            $quote->items()->createMany($cart['lines']);

            return $quote;
        });

        return back()
            ->with('success', "Devis {$quote->reference} établi.")
            ->with('quoteReference', $quote->reference)
            ->with('quoteUrl', $this->downloadUrl($quote));
    }

    /**
     * Téléchargement du PDF.
     *
     * Le jeton tient lieu d'autorisation : le client n'a pas de compte à créer
     * pour récupérer son devis, mais la référence seule ne l'ouvre pas.
     */
    public function download(Quote $quote, string $token): Response
    {
        abort_unless(hash_equals($quote->token, $token), 404);

        return $this->pdf->forQuote($quote)
            ->download(QuotePdfService::filename($quote->reference));
    }

    /** Lien de téléchargement complet, jeton inclus. */
    private function downloadUrl(Quote $quote): string
    {
        return route('quotes.download', ['quote' => $quote->id, 'token' => $quote->token]);
    }
}
