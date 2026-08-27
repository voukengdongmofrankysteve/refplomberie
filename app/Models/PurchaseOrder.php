<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Bon de commande passé à un fournisseur pour réapprovisionner le stock.
 *
 * @property int $id
 * @property string $reference
 * @property int $supplier_id
 * @property PurchaseOrderStatus $status
 * @property Carbon|null $expected_at
 * @property string|null $note
 * @property int $total
 * @property Carbon|null $received_at
 */
#[Fillable(['reference', 'supplier_id', 'status', 'expected_at', 'note', 'total', 'received_at'])]
class PurchaseOrder extends Model
{
    use Auditable;

    protected $attributes = [
        'status' => PurchaseOrderStatus::Draft->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'expected_at' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        return 'BC-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return HasMany<PurchaseOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /** Le bon peut-il encore recevoir des lignes ? */
    public function isEditable(): bool
    {
        return ! $this->status->isTerminal();
    }

    /** Recalcule le total à partir des lignes — jamais saisi à la main. */
    public function recomputeTotal(): void
    {
        $this->update(['total' => $this->items()->sum('line_total')]);
    }
}
