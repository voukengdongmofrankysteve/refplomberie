<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property int $unit_cost
 * @property int $quantity
 * @property int $line_total
 */
#[Fillable(['purchase_order_id', 'product_id', 'product_name', 'unit_cost', 'quantity', 'line_total'])]
class PurchaseOrderItem extends Model
{
    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
