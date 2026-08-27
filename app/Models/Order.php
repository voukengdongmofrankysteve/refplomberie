<?php

namespace App\Models;

use App\Enums\OrderStatus;
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
 * @property int|null $user_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_address
 * @property OrderStatus $status
 * @property int $subtotal
 * @property int $shipping
 * @property string|null $promo_code
 * @property int $discount
 * @property int $total
 * @property string|null $note
 * @property Carbon|null $created_at
 */
#[Fillable([
    'reference',
    'user_id',
    'customer_name',
    'customer_phone',
    'customer_address',
    'status',
    'subtotal',
    'shipping',
    'promo_code',
    'discount',
    'total',
    'note',
])]
class Order extends Model
{
    use Auditable;

    /**
     * Une commande naît « en attente ».
     *
     * La valeur par défaut de la colonne ne suffit pas : elle ne s'applique
     * qu'à l'insertion, laissant l'instance en mémoire avec un statut nul —
     * ce qui casse toute sérialisation faite juste après la création.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => OrderStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }

    /**
     * Référence lisible affichée au client et sur WhatsApp.
     */
    public static function generateReference(): string
    {
        return 'CMD-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
