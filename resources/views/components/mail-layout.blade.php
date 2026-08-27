@php
    /**
     * Gabarit commun à tous les emails de la boutique.
     *
     * Tables et styles en ligne : c'est la seule mise en page qu'Outlook et
     * Gmail rendent de la même façon. Pas de police distante, pas de CSS
     * externe — les clients mail les ignorent ou les bloquent.
     */
    $store = $store ?? \App\Models\StoreSetting::current();
    $green = '#25D366';
    $ink = '#1A1A2E';
    $muted = '#4A4A6A';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $store->name }}</title>
</head>
<body style="margin:0; padding:0; background:#F8F9FA; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif; color:{{ $ink }};">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F8F9FA; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#FFFFFF; border:1px solid #E9ECEF; border-radius:14px; overflow:hidden;">

                {{-- En-tête --}}
                <tr>
                    <td style="padding:22px 26px; border-bottom:3px solid {{ $green }};">
                        <div style="font-size:20px; font-weight:bold; color:{{ $ink }};">
                            Réf.<span style="color:{{ $green }};">Plomberie</span>
                        </div>
                        <div style="font-size:10px; letter-spacing:1.4px; text-transform:uppercase; color:{{ $muted }}; margin-top:2px;">
                            Matériaux &amp; Équipements
                        </div>
                    </td>
                </tr>

                {{-- Contenu --}}
                <tr>
                    <td style="padding:26px;">
                        {{ $slot }}
                    </td>
                </tr>

                {{-- Pied --}}
                <tr>
                    <td style="padding:18px 26px; background:#F8F9FA; border-top:1px solid #E9ECEF; font-size:11px; color:{{ $muted }}; line-height:1.6;">
                        <strong style="color:{{ $ink }};">{{ $store->name }}</strong><br>
                        {{ $store->address }} &middot; {{ $store->phone }}<br>
                        <a href="{{ url('/') }}" style="color:{{ $green }}; text-decoration:none;">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>

                        @isset($unsubscribeUrl)
                            <br><br>
                            Vous recevez cet email parce que vous avez activé les notifications
                            dans votre espace client.
                            <a href="{{ $unsubscribeUrl }}" style="color:{{ $muted }};">
                                Gérer mes préférences
                            </a>.
                        @endisset
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
