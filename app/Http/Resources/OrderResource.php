<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Services\CustomerNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
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
            'customerAddress' => $this->customer_address,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'promoCode' => $this->promo_code,
            'discount' => $this->discount,
            'total' => $this->total,
            'note' => $this->note,
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
            // Message de suivi pré-rempli : l'administrateur prévient le
            // client en un clic, sans rien saisir.
            'whatsAppUrl' => app(CustomerNotifier::class)->orderStatusWhatsAppUrl($this->resource),
            'emailNotified' => $this->whenLoaded(
                'user',
                fn (): bool => $this->user?->acceptsEmail('orders') ?? false,
            ),
        ];
    }
}
