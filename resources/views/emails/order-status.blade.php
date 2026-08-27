<x-mail-layout :title="'Commande ' . $order->reference" :unsubscribe-url="$preferencesUrl">
    <p style="margin:0 0 14px; font-size:15px;">Bonjour {{ $order->customer_name }},</p>

    <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#4A4A6A;">
        {{ $headline }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
           style="background:#F8F9FA; border:1px solid #E9ECEF; border-radius:10px; margin-bottom:18px;">
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:10px; letter-spacing:1.2px; text-transform:uppercase; color:#4A4A6A; font-weight:bold;">
                    Référence
                </div>
                <div style="font-size:16px; font-weight:bold; margin-top:2px;">{{ $order->reference }}</div>
                <div style="margin-top:10px;">
                    <span style="display:inline-block; background:#E8F5E9; color:#1DA851; border-radius:999px; padding:5px 12px; font-size:12px; font-weight:bold;">
                        {{ $order->status->label() }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; margin-bottom:6px;">
        @foreach ($order->items as $item)
            <tr>
                <td style="padding:7px 0; border-bottom:1px solid #E9ECEF; color:#1A1A2E;">
                    {{ $item->product_name }}
                    <span style="color:#4A4A6A;">&times; {{ $item->quantity }}</span>
                </td>
                <td style="padding:7px 0; border-bottom:1px solid #E9ECEF; text-align:right; white-space:nowrap;">
                    {{ $money($item->line_total) }}
                </td>
            </tr>
        @endforeach

        @if ($order->discount > 0)
            <tr>
                <td style="padding:7px 0; color:#1DA851;">Remise {{ $order->promo_code }}</td>
                <td style="padding:7px 0; text-align:right; color:#1DA851;">− {{ $money($order->discount) }}</td>
            </tr>
        @endif

        <tr>
            <td style="padding:7px 0; color:#4A4A6A;">Livraison</td>
            <td style="padding:7px 0; text-align:right;">
                {{ $order->shipping === 0 ? 'Offerte' : $money($order->shipping) }}
            </td>
        </tr>
        <tr>
            <td style="padding:10px 0 0; font-weight:bold; font-size:15px; border-top:2px solid #25D366;">Total</td>
            <td style="padding:10px 0 0; text-align:right; font-weight:bold; font-size:15px; border-top:2px solid #25D366;">
                {{ $money($order->total) }}
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin:26px 0 10px;">
        <a href="{{ $ordersUrl }}"
           style="display:inline-block; background:#25D366; color:#FFFFFF; text-decoration:none; font-weight:bold; font-size:14px; padding:13px 26px; border-radius:10px;">
            Suivre ma commande
        </a>
    </div>

    <p style="margin:0; font-size:12px; line-height:1.6; color:#4A4A6A; text-align:center;">
        Une question ? Écrivez-nous sur WhatsApp au {{ $store->phone }}.
    </p>
</x-mail-layout>
