<?php

namespace App\Services\Analytics;

use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;

/**
 * Rendu PDF du tableau de bord d'audience.
 *
 * Même gabarit visuel que les devis (resources/views/pdf/quote.blade.php) :
 * un responsable qui a déjà vu un devis Réf. Plomberie reconnaît le document.
 */
class AnalyticsPdfService
{
    public function forPeriod(Period $period, AnalyticsReport $report, string $geoDriver): PdfWrapper
    {
        return Pdf::loadView('pdf.analytics', [
            'store' => StoreSetting::current(),
            'watermark' => config('shop.watermark'),
            'period' => $period,
            'summary' => $report->summary(),
            'previous' => $report->previousSummary(),
            'series' => $report->series(),
            'topPages' => $report->topPages(10),
            'topProducts' => $report->topProducts(10),
            'topSearches' => $report->topSearches(10),
            'countries' => $report->breakdown('country', limit: 8, codeColumn: 'country_code'),
            'cities' => $report->breakdown('city', limit: 8),
            'devices' => $report->breakdown('device', limit: 5),
            'platforms' => $report->breakdown('platform', limit: 6),
            'browsers' => $report->breakdown('browser', limit: 6),
            'sources' => $report->breakdown('source', limit: 5),
            'referrers' => $report->referrers(8),
            'hours' => $report->byHour(),
            'weekdays' => $report->byWeekday(),
            'actions' => $report->actions(),
            'funnel' => $report->funnel(),
            'geoDriver' => $geoDriver,
            'generatedAt' => now(),
        ])->setPaper('a4');
    }

    public static function filename(Period $period): string
    {
        return 'audience-'.$period->from->toDateString().'-'.$period->to->toDateString().'.pdf';
    }
}
