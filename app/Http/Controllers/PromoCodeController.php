<?php

namespace App\Http\Controllers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Models\PromoCode;
use App\Services\CartPricer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function __construct(private readonly CartPricer $pricer) {}

    /**
     * Vérifie un code saisi dans le panier.
     *
     * Réponse JSON plutôt qu'Inertia : le panier interroge cette route pendant
     * la saisie, sans quitter ni recharger la page. La remise renvoyée est
     * indicative — c'est OrderController qui la recalcule à l'enregistrement.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'subtotal' => ['required', 'integer', 'min:0'],
        ]);

        $promo = PromoCode::findByCode($validated['code']);
        $subtotal = (int) $validated['subtotal'];

        if ($promo === null) {
            return response()->json([
                'valid' => false,
                'message' => 'Ce code promo est introuvable.',
            ]);
        }

        $reason = $promo->rejectionReason($subtotal);

        if ($reason !== null) {
            return response()->json(['valid' => false, 'message' => $reason]);
        }

        $discount = $promo->discountFor($subtotal);

        Analytics::record(
            AnalyticsEvent::PromoApplied,
            subject: $promo,
            label: $promo->code,
            value: $discount,
        );

        return response()->json([
            'valid' => true,
            'code' => $promo->code,
            'label' => $promo->label,
            'advantage' => $promo->advantage(),
            'discount' => $discount,
            'shipping' => $this->pricer->shippingFor($subtotal - $discount),
            'message' => 'Code appliqué : '.$promo->advantage().'.',
        ]);
    }
}
