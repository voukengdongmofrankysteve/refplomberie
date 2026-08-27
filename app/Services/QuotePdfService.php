<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Quote;
use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;

/**
 * Rendu PDF des devis et des factures pro forma.
 *
 * Les deux documents partagent la même mise en page : seuls le titre, la
 * référence et la mention de validité changent.
 */
class QuotePdfService
{
    /** Devis client, avec sa date de validité. */
    public function forQuote(Quote $quote): PdfWrapper
    {
        $quote->loadMissing('items');

        return $this->render([
            'documentTitle' => 'DEVIS',
            'reference' => $quote->reference,
            'issuedAt' => $quote->created_at,
            'validUntil' => $quote->valid_until,
            'customer' => [
                'name' => $quote->customer_name,
                'company' => $quote->customer_company,
                'phone' => $quote->customer_phone,
                'email' => $quote->customer_email,
                'address' => $quote->customer_address,
            ],
            'items' => $quote->items
                ->map(fn ($item): array => [
                    'name' => $item->product_name,
                    'unitPrice' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'lineTotal' => $item->line_total,
                ])
                ->all(),
            'subtotal' => $quote->subtotal,
            'discount' => 0,
            'promoCode' => null,
            'shipping' => $quote->shipping,
            'total' => $quote->total,
            'note' => $quote->note,
        ]);
    }

    /** Facture pro forma reprenant une commande enregistrée. */
    public function forOrder(Order $order): PdfWrapper
    {
        $order->loadMissing('items');

        return $this->render([
            'documentTitle' => 'FACTURE PRO FORMA',
            'reference' => $order->reference,
            'issuedAt' => $order->created_at,
            'validUntil' => null,
            'customer' => [
                'name' => $order->customer_name,
                'company' => null,
                'phone' => $order->customer_phone,
                'email' => $order->user?->email,
                'address' => $order->customer_address,
            ],
            'items' => $order->items
                ->map(fn ($item): array => [
                    'name' => $item->product_name,
                    'unitPrice' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'lineTotal' => $item->line_total,
                ])
                ->all(),
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'promoCode' => $order->promo_code,
            'shipping' => $order->shipping,
            'total' => $order->total,
            'note' => $order->note,
        ]);
    }

    /** Nom de fichier proposé au téléchargement. */
    public static function filename(string $reference): string
    {
        return $reference.'.pdf';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(array $data): PdfWrapper
    {
        return Pdf::loadView('pdf.quote', [
            ...$data,
            'store' => StoreSetting::current(),
            'watermark' => config('shop.watermark'),
        ])->setPaper('a4');
    }
}
