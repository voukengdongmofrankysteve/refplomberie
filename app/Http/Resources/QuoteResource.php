<?php

namespace App\Http\Resources;

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
            'customerPhone' => $this->customer_phone,
            'customerEmail' => $this->customer_email,
            'customerCompany' => $this->customer_company,
            'customerAddress' => $this->customer_address,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'note' => $this->note,
            'validUntil' => $this->valid_until->format('d/m/Y'),
            'isExpired' => $this->isExpired(),
            'createdAt' => $this->created_at?->format('d/m/Y H:i') ?? '',
            'accountEmail' => $this->whenLoaded('user', fn () => $this->user?->email),
            'items' => $this->whenLoaded(
                'items',
                fn () => $this->items
                    ->map(fn ($item): array => [
                        'id' => $item->id,
                        'productName' => $item->product_name,
                        'unitPrice' => $item->unit_price,
                        'quantity' => $item->quantity,
                        'lineTotal' => $item->line_total,
                    ])
                    ->all(),
            ),
            'itemsCount' => $this->whenCounted('items'),
            'pdfUrl' => route('admin.quotes.pdf', $this->id),
        ];
    }
}
