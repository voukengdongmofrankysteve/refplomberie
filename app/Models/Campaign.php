<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Models\Concerns\Auditable;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Campagne promotionnelle adressée aux clients abonnés.
 *
 * @property int $id
 * @property string $subject
 * @property string $body
 * @property string|null $promo_code
 * @property array<int, int>|null $product_ids
 * @property array<int, string>|null $channels
 * @property int $pushed_count
 * @property CampaignStatus $status
 * @property int $recipients_count
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 */
#[Fillable([
    'subject',
    'body',
    'promo_code',
    'product_ids',
    'channels',
    'status',
    'recipients_count',
    'pushed_count',
    'sent_at',
])]
class Campaign extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_ids' => 'array',
            'channels' => 'array',
            'status' => CampaignStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    /** La campagne doit-elle partir par ce canal ? */
    public function usesChannel(string $channel): bool
    {
        // Sans choix explicite — campagnes créées avant l'ajout des canaux —
        // seul l'email partait : on conserve ce comportement.
        return in_array($channel, $this->channels ?? ['email'], strict: true);
    }

    /**
     * Corps découpé en paragraphes.
     *
     * L'administrateur saisit du texte brut : les lignes vides séparent les
     * paragraphes, et rien n'est interprété comme du HTML.
     *
     * @return array<int, string>
     */
    public function paragraphs(): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R{2,}/', $this->body) ?: []),
            fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * Produits mis en avant, prêts pour le gabarit d'email.
     *
     * Les URL sont absolues : un client mail ne sait pas résoudre un chemin
     * relatif, et les images ne s'afficheraient pas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function productCards(): array
    {
        $ids = $this->product_ids ?? [];

        if ($ids === []) {
            return [];
        }

        $products = Product::query()
            ->active()
            ->with('category')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $cards = [];

        // On respecte l'ordre choisi dans le back-office.
        foreach ($ids as $id) {
            $product = $products->get($id);

            if ($product === null) {
                continue;
            }

            $cards[] = [
                'name' => $product->name,
                'category' => $product->category->label,
                'price' => $product->price,
                'oldPrice' => $product->old_price,
                'image' => ProductImageService::absoluteUrl($product->image),
                'url' => route('shop.product', $product),
            ];
        }

        return $cards;
    }
}
