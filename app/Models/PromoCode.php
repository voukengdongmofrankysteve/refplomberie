<?php

namespace App\Models;

use App\Enums\PromoCodeType;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property string|null $label
 * @property PromoCodeType $type
 * @property int $value
 * @property int $min_subtotal
 * @property int|null $max_uses
 * @property int $used_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
#[Fillable([
    'code',
    'label',
    'type',
    'value',
    'min_subtotal',
    'max_uses',
    'used_count',
    'starts_at',
    'ends_at',
    'is_active',
])]
class PromoCode extends Model
{
    use Auditable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PromoCodeType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Les codes sont insensibles à la casse : on les range en majuscules.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(trim($value)),
        );
    }

    /** Retrouve un code quelle que soit la casse saisie par le client. */
    public static function findByCode(string $code): ?self
    {
        return self::query()->where('code', Str::upper(trim($code)))->first();
    }

    /** Le code est-il utilisable maintenant, indépendamment du panier ? */
    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        return $this->ends_at === null || $now->lte($this->ends_at);
    }

    /**
     * Remise applicable à un sous-total, plafonnée à ce sous-total : une
     * commande ne peut pas devenir négative.
     */
    public function discountFor(int $subtotal): int
    {
        if (! $this->isRedeemable() || $subtotal < $this->min_subtotal) {
            return 0;
        }

        $discount = $this->type === PromoCodeType::Percent
            ? (int) floor($subtotal * $this->value / 100)
            : $this->value;

        return min($discount, $subtotal);
    }

    /**
     * Raison du refus, affichable au client — `null` si le code est valable.
     */
    public function rejectionReason(int $subtotal): ?string
    {
        if (! $this->isRedeemable()) {
            return 'Ce code promo n’est plus valable.';
        }

        if ($subtotal < $this->min_subtotal) {
            return 'Ce code s’applique à partir de '
                .number_format($this->min_subtotal, 0, ',', ' ').' FCFA d’achat.';
        }

        return null;
    }

    /** Description courte de l'avantage, pour l'affichage. */
    public function advantage(): string
    {
        return $this->type === PromoCodeType::Percent
            ? "-{$this->value} %"
            : '-'.number_format($this->value, 0, ',', ' ').' FCFA';
    }
}
