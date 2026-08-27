<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\PromoCode;
use App\Services\CustomerNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Campagnes promotionnelles adressées aux clients abonnés.
 */
class CampaignController extends Controller
{
    public function __construct(private readonly CustomerNotifier $notifier) {}

    public function index(): Response
    {
        return Inertia::render('admin/campaigns/index', [
            'campaigns' => Campaign::latest()
                ->paginate(10)
                ->through(fn (Campaign $campaign): array => [
                    'id' => $campaign->id,
                    'subject' => $campaign->subject,
                    'body' => $campaign->body,
                    'promoCode' => $campaign->promo_code,
                    'productIds' => $campaign->product_ids ?? [],
                    'channels' => $campaign->channels ?? ['email'],
                    'pushedCount' => $campaign->pushed_count,
                    'status' => $campaign->status->value,
                    'statusLabel' => $campaign->status->label(),
                    'recipientsCount' => $campaign->recipients_count,
                    'sentAt' => $campaign->sent_at?->format('d/m/Y H:i'),
                    'createdAt' => $campaign->created_at?->format('d/m/Y H:i') ?? '',
                ]),
            // Nombre de clients qui recevraient un envoi lancé maintenant.
            'audience' => $this->notifier->promotionAudience(),
            // Deux publics distincts : une adresse confirmée d'un côté, un
            // appareil enregistré de l'autre.
            'pushAudience' => $this->notifier->pushAudience(),
            'products' => Product::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name', 'price'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                ])
                ->all(),
            'promoCodes' => PromoCode::where('is_active', true)
                ->orderBy('code')
                ->pluck('code')
                ->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $campaign = Campaign::create($this->validated($request));

        return back()->with('success', "Campagne « {$campaign->subject} » enregistrée.");
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        // Une campagne partie est un fait historique : on ne réécrit pas ce
        // que les clients ont déjà reçu.
        if ($campaign->status !== CampaignStatus::Draft) {
            return back()->with('error', 'Une campagne déjà envoyée ne peut plus être modifiée.');
        }

        $campaign->update($this->validated($request));

        return back()->with('success', 'Campagne mise à jour.');
    }

    /** Diffuse la campagne aux abonnés aux promotions. */
    public function send(Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== CampaignStatus::Draft) {
            return back()->with('error', 'Cette campagne a déjà été envoyée.');
        }

        $this->notifier->sendCampaign($campaign);
        $campaign->refresh();

        $parts = [];

        if ($campaign->recipients_count > 0) {
            $parts[] = "{$campaign->recipients_count} email(s)";
        }

        if ($campaign->pushed_count > 0) {
            $parts[] = "{$campaign->pushed_count} notification(s) push";
        }

        return back()->with(
            $parts === [] ? 'error' : 'success',
            $parts === []
                ? 'Aucun destinataire : personne n’est encore abonné aux promotions.'
                : 'Campagne envoyée — '.implode(', ', $parts).'.',
        );
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('success', 'Campagne supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:4000'],
            'promo_code' => ['nullable', 'string', 'max:40', 'exists:promo_codes,code'],
            'product_ids' => ['array', 'max:6'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            // La notification en base part toujours : elle ne se choisit pas.
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['string', 'in:email,push'],
        ], attributes: [
            'subject' => 'objet',
            'body' => 'message',
            'promo_code' => 'code promo',
            'product_ids' => 'produits mis en avant',
            'channels' => 'canaux',
        ]);
    }
}
