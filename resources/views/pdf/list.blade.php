@php
    /**
     * Gabarit partagé par tous les exports de liste du back-office — mêmes
     * couleurs et la même en-tête que resources/views/pdf/quote.blade.php.
     * Rendu par dompdf : pas de flexbox ni de grille, on reste sur des tables.
     */
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 26px 32px 40px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1A1A2E;
            line-height: 1.45;
        }

        .brand-name { font-size: 17px; font-weight: bold; color: #1A1A2E; }
        .brand-name span { color: #25D366; }
        .brand-baseline { font-size: 8px; color: #4A4A6A; letter-spacing: .08em; text-transform: uppercase; }
        .muted { color: #4A4A6A; }
        .small { font-size: 8.5px; }

        .doc-type { font-size: 15px; font-weight: bold; letter-spacing: .08em; color: #25D366; }
        .doc-ref { font-size: 10.5px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .rule { border-bottom: 2px solid #25D366; height: 0; margin: 10px 0 16px; }

        .items th {
            background: #1A1A2E;
            color: #FFFFFF;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 6px 8px;
            text-align: left;
        }
        .items td { padding: 5px 8px; border-bottom: 1px solid #E9ECEF; }
        .items tr:nth-child(even) td { background: #F8F9FA; }
        .num { text-align: right; }

        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7.5px;
            color: #4A4A6A;
            border-top: 1px solid #E9ECEF;
            padding-top: 5px;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="width: 55%">
            <div class="brand-name">Réf.<span>Plomberie</span></div>
            <div class="brand-baseline">{{ $watermark['baseline'] }}</div>
            <div class="muted small" style="margin-top: 6px">
                {{ $store->address }}<br>
                {{ $store->phone }} &middot; {{ $store->email }}
            </div>
        </td>
        <td style="width: 45%; text-align: right">
            <div class="doc-type">{{ mb_strtoupper($title) }}</div>
            @if ($subtitle)
                <div class="doc-ref">{{ $subtitle }}</div>
            @endif
            <div class="muted small" style="margin-top: 5px">
                Généré le {{ $generatedAt->format('d/m/Y à H:i') }}
            </div>
        </td>
    </tr>
</table>

<div class="rule"></div>

<table class="items">
    <thead>
        <tr>
            @foreach ($columns as $column)
                <th class="{{ ($column['align'] ?? 'left') === 'right' ? 'num' : '' }}">
                    {{ $column['label'] }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                @foreach ($row as $index => $value)
                    <td class="{{ ($columns[$index]['align'] ?? 'left') === 'right' ? 'num' : '' }}">
                        {{ $value }}
                    </td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($columns) }}" class="muted" style="text-align: center; padding: 16px">
                    Aucune donnée pour cette sélection.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@if ($note)
    <p class="muted small" style="margin-top: 10px">{{ $note }}</p>
@endif

<div class="footer">
    {{ count($rows) }} ligne{{ count($rows) > 1 ? 's' : '' }} — {{ $store->name }}
</div>

</body>
</html>
