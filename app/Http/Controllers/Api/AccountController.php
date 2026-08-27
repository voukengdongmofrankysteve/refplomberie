<?php

namespace App\Http\Controllers\Api;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductResource;
use App\Http\Resources\ReviewResource;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Review;
use App\Services\CartPricer;
use App\Services\EmailOptInService;
use App\Services\PurchaseVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Actions du compte client depuis l'application : favoris, avis, code promo
 * et préférences de notification.
 *
 * Regroupées ici parce qu'elles partagent le même contexte — un client
 * authentifié agissant sur son propre compte — et tiennent chacune en
 * quelques lignes.
 */
class AccountController extends Controller
{
    public function __construct(
        private readonly CartPricer $pricer,
        private readonly EmailOptInService $optIn,
        private readonly PurchaseVerification $purchases,
    ) {}

    public function favorites(Request $request): JsonResponse
    {
        $favorites = $request->user()
            ->favorites()
            ->with(['category', 'priceTiers'])
            ->get();

        return response()->json([
            'data' => ProductResource::collection($favorites)->resolve($request),
        ]);
    }

    /** Bascule un produit en favori, et renvoie l'état obtenu. */
    public function toggleFavorite(Request $request, Product $product): JsonResponse
    {
        $result = $request->user()->favorites()->toggle($product->id);
        $added = in_array($product->id, $result['attached'], strict: true);

        if ($added) {
            Analytics::record(
                AnalyticsEvent::FavoriteAdded,
                subject: $product,
                label: $product->name,
            );
        }

        return response()->json([
            'favorite' => $added,
            'message' => $added
                ? "« {$product->name} » ajouté à vos favoris."
                : "« {$product->name} » retiré de vos favoris.",
        ]);
    }

    /** Publie un avis, puis recalcule la note du produit. */
    public function storeReview(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
        ], attributes: ['rating' => 'note', 'body' => 'commentaire']);

        if (! $this->purchases->hasConfirmedPurchase($request->user(), $product)) {
            return response()->json([
                'message' => 'Seuls les clients ayant acheté ce produit peuvent laisser un avis.',
            ], 422);
        }

        $alreadyReviewed = Review::where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'message' => 'Vous avez déjà publié un avis sur ce produit.',
            ], 422);
        }

        $review = Review::create([
            ...$data,
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'verified_purchase' => true,
        ]);

        $product->refreshRating();

        return response()->json([
            'data' => (new ReviewResource($review->load('user')))->resolve($request),
        ], 201);
    }

    /**
     * Vérifie un code promo saisi dans le panier.
     *
     * Purement indicatif : c'est l'enregistrement de la commande qui fait foi.
     */
    public function checkPromoCode(Request $request): JsonResponse
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

    /** Envoie le code de confirmation de l'adresse de notification. */
    public function sendEmailCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
        ], attributes: ['email' => 'adresse email']);

        $this->optIn->sendCode($request->user(), $data['email']);

        return response()->json([
            'message' => 'Code envoyé. Vérifiez votre boîte de réception.',
        ]);
    }

    public function confirmEmailCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], attributes: ['code' => 'code'], messages: [
            'code.size' => 'Le code compte 6 chiffres.',
        ]);

        $this->optIn->confirm($request->user(), $data['code']);

        return response()->json([
            'message' => 'Adresse confirmée. Vos notifications sont activées.',
            'notifications' => $this->notificationState($request),
        ]);
    }

    /**
     * Coche ou décoche un thème.
     *
     * Aucune adresse confirmée n'est exigée : sur mobile, la plupart des
     * clients veulent les promotions par notification et rien d'autre.
     * L'envoi par email vérifie l'adresse de son côté.
     */
    public function updateNotifications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notify_order_updates' => ['required', 'boolean'],
            'notify_promotions' => ['required', 'boolean'],
            'notify_push' => ['sometimes', 'boolean'],
        ]);

        $request->user()->update($data);

        return response()->json(['notifications' => $this->notificationState($request)]);
    }

    public function disableNotifications(Request $request): JsonResponse
    {
        $this->optIn->disable($request->user());

        return response()->json([
            'message' => 'Notifications par email désactivées.',
            'notifications' => $this->notificationState($request),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationState(Request $request): array
    {
        $user = $request->user()->fresh();

        return [
            'email' => $user->notification_email,
            'verified' => $user->hasVerifiedNotificationEmail(),
            'orderUpdates' => $user->notify_order_updates,
            'promotions' => $user->notify_promotions,
            'push' => $user->notify_push,
            'devices' => $user->deviceTokens()->count(),
        ];
    }
}
