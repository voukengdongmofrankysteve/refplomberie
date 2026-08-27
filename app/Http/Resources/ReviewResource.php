<?php

namespace App\Http\Resources;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $author = $this->user?->name ?? 'Client';

        return [
            'id' => $this->id,
            'author' => $author,
            'avatar' => Str::upper(Str::substr($author, 0, 1)),
            'rating' => $this->rating,
            'text' => $this->body,
            'verifiedPurchase' => $this->verified_purchase,
            'date' => $this->created_at?->format('d/m/Y') ?? '',
        ];
    }
}
