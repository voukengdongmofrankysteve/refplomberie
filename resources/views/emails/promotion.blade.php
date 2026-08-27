<x-mail-layout :title="$subject" :unsubscribe-url="$preferencesUrl">
    <p style="margin:0 0 16px; font-size:15px;">Bonjour {{ $name }},</p>

    <div style="font-size:14px; line-height:1.7; color:#1A1A2E;">
        @foreach ($paragraphs as $paragraph)
            <p style="margin:0 0 14px;">{{ $paragraph }}</p>
        @endforeach
    </div>

    @if (count($products) > 0)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:20px 0 6px;">
            @foreach ($products as $product)
                <tr>
                    <td style="padding:10px; border:1px solid #E9ECEF; border-radius:10px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td width="72" style="padding-right:12px;">
                                    <img src="{{ $product['image'] }}" alt="" width="72"
                                         style="width:72px; height:72px; object-fit:cover; border-radius:8px; border:1px solid #E9ECEF; display:block;">
                                </td>
                                <td style="vertical-align:middle;">
                                    <div style="font-size:14px; font-weight:bold; color:#1A1A2E;">{{ $product['name'] }}</div>
                                    <div style="font-size:12px; color:#4A4A6A; margin:2px 0 6px;">{{ $product['category'] }}</div>
                                    <div style="font-size:15px; font-weight:bold; color:#25D366;">
                                        {{ $money($product['price']) }}
                                        @if ($product['oldPrice'])
                                            <span style="font-size:12px; color:#4A4A6A; text-decoration:line-through; font-weight:normal;">
                                                {{ $money($product['oldPrice']) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td width="90" style="text-align:right; vertical-align:middle;">
                                    <a href="{{ $product['url'] }}"
                                       style="display:inline-block; background:#1A1A2E; color:#FFFFFF; text-decoration:none; font-size:12px; font-weight:bold; padding:8px 12px; border-radius:8px;">
                                        Voir
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="height:10px; line-height:10px;">&nbsp;</td></tr>
            @endforeach
        </table>
    @endif

    @if ($promoCode)
        <div style="text-align:center; background:#E8F5E9; border:1px dashed #25D366; border-radius:12px; padding:16px; margin:18px 0;">
            <div style="font-size:11px; letter-spacing:1.2px; text-transform:uppercase; color:#4A4A6A; font-weight:bold;">
                Votre code promo
            </div>
            <div style="font-size:24px; font-weight:bold; letter-spacing:3px; color:#1A1A2E; margin-top:4px;">
                {{ $promoCode }}
            </div>
            <div style="font-size:12px; color:#4A4A6A; margin-top:4px;">
                À saisir dans le panier avant de valider.
            </div>
        </div>
    @endif

    <div style="text-align:center; margin:24px 0 4px;">
        <a href="{{ $shopUrl }}"
           style="display:inline-block; background:#25D366; color:#FFFFFF; text-decoration:none; font-weight:bold; font-size:14px; padding:13px 26px; border-radius:10px;">
            Voir la boutique
        </a>
    </div>
</x-mail-layout>
