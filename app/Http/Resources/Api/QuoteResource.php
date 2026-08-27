<?php

namespace App\Http\Resources\Api;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quote
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'customerName' => $this->customer_name,
            'customerCompany' => $this->customer_company,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'note' => $this->note,
            'validUntil' => $this->valid_until->toDateString(),
            'isExpired' => $this->isExpired(),
            'createdAt' => $this->created_at?->toIso8601String(),
            // Jeton compris : l'application télécharge le PDF sans session.
            'pdfUrl' => route('quotes.download', [
                'quote' => $this->id,
                'token' => $this->token,
            ]),
            'items' => $this->whenLoaded(
                'items',
                fn (): array => $this->items
                    ->map(fn ($item): array => [
                        'id' => $item->id,
                        'productName' => $item->product_name,
                        'unitPrice' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'lineTotal' => $item->line_total,
                    ])
                    ->all(),
            ),
        ];
    }
}
