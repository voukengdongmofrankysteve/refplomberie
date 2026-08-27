@php
    /**
     * Gabarit partagé par les devis et les factures pro forma.
     * Rendu par dompdf : pas de flexbox ni de grille, on reste sur des tables.
     */
    $money = fn (int $amount): string => number_format($amount, 0, ',', ' ') . ' FCFA';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }} {{ $reference }}</title>
    <style>
        @page { margin: 28px 34px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5px;
            color: #1A1A2E;
            line-height: 1.5;
        }

        .brand-name { font-size: 19px; font-weight: bold; color: #1A1A2E; }
        .brand-name span { color: #25D366; }
        .brand-baseline { font-size: 9px; color: #4A4A6A; letter-spacing: .08em; text-transform: uppercase; }
        .muted { color: #4A4A6A; }
        .small { font-size: 9px; }

        .doc-type { font-size: 16px; font-weight: bold; letter-spacing: .1em; color: #25D366; }
        .doc-ref { font-size: 12px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .rule { border-bottom: 2px solid #25D366; height: 0; margin: 12px 0 18px; }

        .panel {
            background: #F8F9FA;
            border: 1px solid #E9ECEF;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: #4A4A6A;
            font-weight: bold;
        }

        .items { margin-top: 22px; }
        .items th {
            background: #1A1A2E;
            color: #FFFFFF;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 8px 10px;
            text-align: left;
        }
        .items td { padding: 8px 10px; border-bottom: 1px solid #E9ECEF; }
        .num { text-align: right; }

        .totals td { padding: 5px 10px; }
        .totals .grand {
            background: #E8F5E9;
            font-size: 12.5px;
            font-weight: bold;
            border-top: 2px solid #25D366;
        }

        .validity {
            margin-top: 18px;
            border-left: 3px solid #25D366;
            background: #E8F5E9;
            padding: 9px 12px;
            border-radius: 0 6px 6px 0;
        }

        .footer {
            position: fixed;
            bottom: -8px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #4A4A6A;
            border-top: 1px solid #E9ECEF;
            padding-top: 6px;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="width: 58%">
            <div class="brand-name">Réf.<span>Plomberie</span></div>
            <div class="brand-baseline">{{ $watermark['baseline'] }}</div>
            <div class="muted small" style="margin-top: 8px">
                {{ $store->address }}<br>
                {{ $store->phone }} &middot; {{ $store->email }}<br>
                {{ $store->hours }}
            </div>
        </td>
        <td style="width: 42%; text-align: right">
            <div class="doc-type">{{ $documentTitle }}</div>
            <div class="doc-ref">{{ $reference }}</div>
            <div class="muted small" style="margin-top: 6px">
                Émis le {{ $issuedAt?->format('d/m/Y') ?? now()->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>

<div class="rule"></div>

<table>
    <tr>
        <td style="width: 50%; padding-right: 8px">
            <div class="panel">
                <div class="label">Client</div>
                <div style="font-weight: bold; margin-top: 3px">{{ $customer['name'] }}</div>
                @if ($customer['company'])
                    <div class="muted">{{ $customer['company'] }}</div>
                @endif
                <div class="muted small" style="margin-top: 4px">
                    {{ $customer['phone'] }}
                    @if ($customer['email'])<br>{{ $customer['email'] }}@endif
                    @if ($customer['address'])<br>{{ $customer['address'] }}@endif
                </div>
            </div>
        </td>
        <td style="width: 50%; padding-left: 8px">
            <div class="panel">
                <div class="label">Émetteur</div>
                <div style="font-weight: bold; margin-top: 3px">{{ $store->name }}</div>
                <div class="muted small" style="margin-top: 4px">
                    WhatsApp : +{{ $store->whatsapp }}<br>
                    {{ url('/') }}
                </div>
            </div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width: 50%">Désignation</th>
            <th class="num" style="width: 18%">Prix unitaire</th>
            <th class="num" style="width: 12%">Qté</th>
            <th class="num" style="width: 20%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($items as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="num">{{ $money($item['unitPrice']) }}</td>
                <td class="num">{{ $item['quantity'] }}</td>
                <td class="num">{{ $money($item['lineTotal']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals" style="margin-top: 14px">
    <tr>
        <td style="width: 58%"></td>
        <td class="muted" style="width: 22%">Sous-total</td>
        <td class="num" style="width: 20%">{{ $money($subtotal) }}</td>
    </tr>
    @if ($discount > 0)
        <tr>
            <td></td>
            <td class="muted">Remise @if ($promoCode)({{ $promoCode }})@endif</td>
            <td class="num">− {{ $money($discount) }}</td>
        </tr>
    @endif
    <tr>
        <td></td>
        <td class="muted">Livraison</td>
        <td class="num">{{ $shipping === 0 ? 'Offerte' : $money($shipping) }}</td>
    </tr>
    <tr class="grand">
        <td></td>
        <td>Total</td>
        <td class="num">{{ $money($total) }}</td>
    </tr>
</table>

@if ($validUntil)
    <div class="validity">
        <strong>Validité :</strong> ce devis est valable jusqu’au
        <strong>{{ $validUntil->format('d/m/Y') }}</strong>.
        Passé ce délai, les prix sont susceptibles d’être révisés.
    </div>
@endif

@if ($note)
    <div style="margin-top: 16px">
        <div class="label">Observations</div>
        <div class="muted">{{ $note }}</div>
    </div>
@endif

<div style="margin-top: 26px">
    <table>
        <tr>
            <td style="width: 55%" class="muted small">
                Pour accepter ce document, renvoyez-le signé ou confirmez par
                WhatsApp au +{{ $store->whatsapp }} en rappelant la référence
                <strong>{{ $reference }}</strong>.
            </td>
            <td style="width: 45%; text-align: right" class="small">
                <div class="label">Cachet &amp; signature</div>
                <div style="border: 1px solid #E9ECEF; border-radius: 6px; height: 62px; margin-top: 4px"></div>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    {{ $store->name }} — {{ $watermark['baseline'] }} — {{ $store->address }} — {{ $store->phone }}
</div>

</body>
</html>
