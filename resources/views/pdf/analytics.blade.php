@php
    /**
     * Rapport d'audience imprimable — même gabarit que les devis (pdf/quote.blade.php).
     * Rendu par dompdf : pas de flexbox ni de grille, on reste sur des tables.
     */
    $n = fn (int|float $v): string => number_format($v, 0, ',', ' ');
    $money = fn (int $amount): string => $n($amount) . ' FCFA';
    $pct = fn (int|float $v): string => $n($v) . ' %';

    // Barre proportionnelle en table imbriquée : dompdf ignore flexbox, mais
    // rend correctement une cellule dont la largeur est un pourcentage.
    $bar = function (int|float $value, int|float $max) {
        $max = max(1, $max);
        $ratio = max(2, min(100, (int) round($value / $max * 100)));

        return $ratio;
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Audience — {{ $period->from->toDateString() }} au {{ $period->to->toDateString() }}</title>
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

        h2.section {
            font-size: 11px;
            font-weight: bold;
            color: #1A1A2E;
            margin: 20px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #E9ECEF;
            page-break-after: avoid;
        }
        h2.section span { color: #4A4A6A; font-weight: normal; font-size: 8.5px; }

        .kpis td { padding: 3px; }
        .kpi {
            background: #F8F9FA;
            border: 1px solid #E9ECEF;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .kpi .label {
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #4A4A6A;
            font-weight: bold;
        }
        .kpi .value { font-size: 14px; font-weight: bold; margin-top: 2px; }
        .kpi .delta { font-size: 8px; margin-top: 2px; }
        .delta.up { color: #1DA851; }
        .delta.down { color: #B91C1C; }

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
        .num { text-align: right; }

        .bar-track {
            background: #E9ECEF;
            border-radius: 3px;
            height: 7px;
        }
        .bar-fill {
            background: #25D366;
            border-radius: 3px;
            height: 7px;
        }

        .two-col td { vertical-align: top; padding: 0 6px; }
        .two-col td:first-child { padding-left: 0; }
        .two-col td:last-child { padding-right: 0; }

        .funnel-row td { padding: 4px 0; }
        .funnel-step { font-size: 9px; }
        .funnel-value { font-size: 9px; font-weight: bold; text-align: right; }

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

        .page-break { page-break-before: always; }
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
            <div class="doc-type">RAPPORT D’AUDIENCE</div>
            <div class="doc-ref">{{ $period->label }}</div>
            <div class="muted small" style="margin-top: 5px">
                Du {{ $period->from->format('d/m/Y') }} au {{ $period->to->format('d/m/Y') }}<br>
                Généré le {{ $generatedAt->format('d/m/Y à H:i') }}
            </div>
        </td>
    </tr>
</table>

<div class="rule"></div>

{{-- Chiffres clés --}}
<table class="kpis">
    <tr>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Visiteurs</div>
                <div class="value">{{ $n($summary['visitors']) }}</div>
                @php $d = $previous['visitors'] > 0 ? round((($summary['visitors'] - $previous['visitors']) / $previous['visitors']) * 100) : null; @endphp
                <div class="delta {{ $d !== null && $d < 0 ? 'down' : 'up' }}">
                    {{ $d === null ? $summary['newVisitors'] . ' nouveau(x)' : ($d > 0 ? '+' : '') . $d . ' %' }}
                </div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Visites</div>
                <div class="value">{{ $n($summary['sessions']) }}</div>
                <div class="delta muted">{{ $summary['pagesPerSession'] }} page(s)/visite</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Pages vues</div>
                <div class="value">{{ $n($summary['pageViews']) }}</div>
                <div class="delta muted">Durée moy. {{ (int) floor($summary['avgDuration'] / 60) }} min {{ str_pad((string) ($summary['avgDuration'] % 60), 2, '0', STR_PAD_LEFT) }} s</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Chiffre d’affaires</div>
                <div class="value">{{ $money($summary['revenue']) }}</div>
                <div class="delta muted">{{ $summary['orders'] }} commande(s)</div>
            </div>
        </td>
    </tr>
</table>

<table class="kpis" style="margin-top: 6px">
    <tr>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Taux de conversion</div>
                <div class="value">{{ $pct($summary['conversionRate']) }}</div>
                <div class="delta muted">Visites &rarr; commande</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Devis demandés</div>
                <div class="value">{{ $n($summary['quotes']) }}</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Prises de contact</div>
                <div class="value">{{ $n($summary['contacts']) }}</div>
                <div class="delta muted">Messages, WhatsApp, interventions</div>
            </div>
        </td>
        <td style="width: 25%">
            <div class="kpi">
                <div class="label">Actions mesurées</div>
                <div class="value">{{ $n($summary['events']) }}</div>
            </div>
        </td>
    </tr>
</table>

{{-- Fréquentation --}}
<h2 class="section">Fréquentation <span>par {{ $period->granularity === 'hour' ? 'heure' : ($period->granularity === 'month' ? 'mois' : 'jour') }}</span></h2>
<table class="items">
    <thead>
        <tr>
            <th style="width: 30%">Période</th>
            <th class="num" style="width: 18%">Visiteurs</th>
            <th class="num" style="width: 18%">Pages vues</th>
            <th class="num" style="width: 16%">Commandes</th>
            <th class="num" style="width: 18%">Recettes</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($series as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td class="num">{{ $n($row['visitors']) }}</td>
                <td class="num">{{ $n($row['pageViews']) }}</td>
                <td class="num">{{ $n($row['orders']) }}</td>
                <td class="num">{{ $money($row['revenue']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Produits et pages --}}
<h2 class="section">Produits les plus consultés</h2>
@if (count($topProducts) === 0)
    <p class="muted small">Aucune fiche produit consultée sur cette période.</p>
@else
    <table class="items">
        <thead>
            <tr>
                <th style="width: 34%">Produit</th>
                <th class="num" style="width: 13%">Vues</th>
                <th class="num" style="width: 13%">Visiteurs</th>
                <th class="num" style="width: 13%">Vendus</th>
                <th class="num" style="width: 14%">Recettes</th>
                <th class="num" style="width: 13%">Vues&rarr;vente</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($topProducts as $p)
                <tr>
                    <td>{{ $p['name'] }}</td>
                    <td class="num">{{ $n($p['views']) }}</td>
                    <td class="num">{{ $n($p['visitors']) }}</td>
                    <td class="num">{{ $n($p['quantity']) }}</td>
                    <td class="num">{{ $money($p['revenue']) }}</td>
                    <td class="num">{{ $pct($p['conversion']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<table class="two-col" style="margin-top: 16px">
    <tr>
        <td style="width: 50%">
            <h2 class="section">Pages les plus vues</h2>
            @if (count($topPages) === 0)
                <p class="muted small">Aucune donnée sur cette période.</p>
            @else
                @php $maxPage = max(array_column($topPages, 'views')); @endphp
                <table class="items">
                    <tbody>
                        @foreach ($topPages as $row)
                            <tr>
                                <td style="width: 55%">{{ $row['label'] }}</td>
                                <td style="width: 30%">
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: {{ $bar($row['views'], $maxPage) }}%"></div>
                                    </div>
                                </td>
                                <td class="num" style="width: 15%">{{ $n($row['views']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
        <td style="width: 50%">
            <h2 class="section">Recherches</h2>
            @if (count($topSearches) === 0)
                <p class="muted small">Personne n’a encore utilisé la recherche.</p>
            @else
                <table class="items">
                    <tbody>
                        @foreach ($topSearches as $row)
                            <tr>
                                <td style="width: 60%">{{ $row['term'] }}</td>
                                <td class="num" style="width: 20%">{{ $n($row['searches']) }}</td>
                                <td class="num small muted" style="width: 20%">
                                    @if ($row['empty'] > 0) {{ $row['empty'] }} sans résultat @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
    </tr>
</table>

{{-- Géographie --}}
<div class="page-break"></div>

<h2 class="section">Provenance géographique</h2>
<table class="two-col">
    <tr>
        <td style="width: 50%">
            <div class="label" style="margin-bottom: 4px">Pays</div>
            @if (count($countries) === 0)
                <p class="muted small">{{ $geoDriver === 'http' ? 'Aucune visite localisée pour l’instant.' : 'Base de localisation absente.' }}</p>
            @else
                @php $maxCountry = max(array_column($countries, 'sessions')); @endphp
                <table class="items">
                    <tbody>
                        @foreach ($countries as $row)
                            <tr>
                                <td style="width: 45%">{{ $row['name'] }}</td>
                                <td style="width: 35%">
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: {{ $bar($row['sessions'], $maxCountry) }}%"></div>
                                    </div>
                                </td>
                                <td class="num" style="width: 20%">{{ $n($row['sessions']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
        <td style="width: 50%">
            <div class="label" style="margin-bottom: 4px">Villes</div>
            @if (count($cities) === 0)
                <p class="muted small">Aucune donnée sur cette période.</p>
            @else
                @php $maxCity = max(array_column($cities, 'sessions')); @endphp
                <table class="items">
                    <tbody>
                        @foreach ($cities as $row)
                            <tr>
                                <td style="width: 45%">{{ $row['name'] }}</td>
                                <td style="width: 35%">
                                    <div class="bar-track">
                                        <div class="bar-fill" style="width: {{ $bar($row['sessions'], $maxCity) }}%"></div>
                                    </div>
                                </td>
                                <td class="num" style="width: 20%">{{ $n($row['sessions']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
    </tr>
</table>

<h2 class="section">Provenance du trafic</h2>
@if (count($referrers) === 0)
    <p class="muted small">Aucune donnée sur cette période.</p>
@else
    @php $maxRef = max(array_column($referrers, 'sessions')); @endphp
    <table class="items">
        <tbody>
            @foreach ($referrers as $row)
                <tr>
                    <td style="width: 40%">{{ $row['name'] }}</td>
                    <td style="width: 40%">
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $bar($row['sessions'], $maxRef) }}%"></div>
                        </div>
                    </td>
                    <td class="num" style="width: 20%">{{ $n($row['sessions']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Appareils --}}
<h2 class="section">Appareils, systèmes et navigateurs</h2>
<table class="two-col">
    <tr>
        <td style="width: 34%">
            <div class="label" style="margin-bottom: 4px">Appareils</div>
            <table class="items">
                <tbody>
                    @foreach ($devices as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="num" style="width: 40%">{{ $n($row['sessions']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
        <td style="width: 33%">
            <div class="label" style="margin-bottom: 4px">Systèmes</div>
            <table class="items">
                <tbody>
                    @foreach ($platforms as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="num" style="width: 40%">{{ $n($row['sessions']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
        <td style="width: 33%">
            <div class="label" style="margin-bottom: 4px">Site ou application</div>
            <table class="items">
                <tbody>
                    @foreach ($sources as $row)
                        <tr>
                            <td>{{ $row['name'] === 'app' ? 'Application mobile' : 'Site web' }}</td>
                            <td class="num" style="width: 40%">{{ $n($row['sessions']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
    </tr>
</table>

{{-- Rythme --}}
<h2 class="section">Affluence par jour de la semaine</h2>
@php $maxWeekday = max(array_column($weekdays, 'views')); @endphp
<table class="items">
    <tbody>
        @foreach ($weekdays as $row)
            <tr>
                <td style="width: 25%">{{ $row['day'] }}</td>
                <td style="width: 55%">
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ $bar($row['views'], $maxWeekday) }}%"></div>
                    </div>
                </td>
                <td class="num" style="width: 20%">{{ $n($row['views']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Entonnoir --}}
<div class="page-break"></div>

<h2 class="section">Parcours d’achat <span>visiteurs distincts à chaque étape</span></h2>
<table class="items">
    <tbody>
        @foreach ($funnel as $step)
            <tr class="funnel-row">
                <td class="funnel-step" style="width: 30%">{{ $step['step'] }}</td>
                <td style="width: 50%">
                    <div class="bar-track">
                        <div class="bar-fill" style="width: {{ max(2, $step['share']) }}%"></div>
                    </div>
                </td>
                <td class="funnel-value" style="width: 20%">{{ $n($step['visitors']) }} &middot; {{ $pct($step['share']) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2 class="section">Toutes les actions mesurées</h2>
@php $maxAction = max(array_column($actions, 'count')); @endphp
<table class="items">
    <tbody>
        @foreach ($actions as $row)
            @if ($row['count'] > 0)
                <tr>
                    <td style="width: 40%">{{ $row['label'] }}</td>
                    <td style="width: 40%">
                        <div class="bar-track">
                            <div class="bar-fill" style="width: {{ $bar($row['count'], $maxAction) }}%"></div>
                        </div>
                    </td>
                    <td class="num" style="width: 20%">{{ $n($row['count']) }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>

<div class="footer">
    Mesure interne Réf. Plomberie — aucune donnée transmise à un service tiers, aucun cookie publicitaire.
    @if ($geoDriver === 'http') Localisation fournie par ip-api.com. @endif
    @if ($geoDriver === 'maxmind') Localisation issue de la base GeoLite2 de MaxMind. @endif
</div>

</body>
</html>
