<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int $user_id
 * @property int $rating
 * @property string $body
 * @property bool $verified_purchase
 * @property Carbon|null $created_at
 */
#[Fillable(['product_id', 'user_id', 'rating', 'body', 'verified_purchase'])]
class Review extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['verified_purchase' => 'boolean'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
