<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $quote_id
 * @property int|null $product_id
 * @property string $product_name
 * @property int $unit_price
 * @property int $quantity
 * @property int $line_total
 */
#[Fillable([
    'quote_id',
    'product_id',
    'product_name',
    'unit_price',
    'quantity',
    'line_total',
])]
class QuoteItem extends Model
{
    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
