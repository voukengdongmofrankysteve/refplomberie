<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\PurchaseVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly PurchaseVerification $purchases) {}

    /**
     * Publie l'avis d'un client sur un produit, puis recalcule sa note.
     *
     * Réservé aux clients qui ont réellement acheté ce produit : un avis
     * n'a de valeur que s'il vient de quelqu'un qui l'a reçu.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:5', 'max:2000'],
        ], attributes: [
            'rating' => 'note',
            'body' => 'commentaire',
        ]);

        if (! $this->purchases->hasConfirmedPurchase($request->user(), $product)) {
            return back()->with('error', 'Seuls les clients ayant acheté ce produit peuvent laisser un avis.');
        }

        $alreadyReviewed = Review::where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'Vous avez déjà publié un avis sur ce produit.');
        }

        Review::create([
            ...$data,
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'verified_purchase' => true,
        ]);

        $product->refreshRating();

        return back()->with('success', 'Merci ! Votre avis a été publié.');
    }
}
