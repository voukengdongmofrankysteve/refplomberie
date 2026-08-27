<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $reference
 * @property string $token
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string|null $customer_company
 * @property string|null $customer_address
 * @property QuoteStatus $status
 * @property int $subtotal
 * @property int $shipping
 * @property int $total
 * @property string|null $note
 * @property Carbon $valid_until
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference',
    'token',
    'user_id',
    'customer_name',
    'customer_phone',
    'customer_email',
    'customer_company',
    'customer_address',
    'status',
    'subtotal',
    'shipping',
    'total',
    'note',
    'valid_until',
])]
class Quote extends Model
{
    use Auditable;

    /**
     * Un devis naît « à traiter ».
     *
     * Comme pour les commandes, la valeur par défaut de la colonne ne
     * s'applique qu'à l'insertion et laisserait l'instance en mémoire avec
     * un statut nul.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => QuoteStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'valid_until' => 'date',
        ];
    }

    /** Référence lisible, imprimée en haut du devis. */
    public static function generateReference(): string
    {
        return 'DEV-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    /** Le devis est-il encore dans sa période de validité ? */
    public function isExpired(): bool
    {
        return $this->valid_until->isPast();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<QuoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
