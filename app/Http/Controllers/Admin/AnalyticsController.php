<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Analytics\Event;
use App\Services\Analytics\AnalyticsPdfService;
use App\Services\Analytics\AnalyticsReport;
use App\Services\Analytics\Geolocator;
use App\Services\Analytics\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tableau de bord d'audience du back-office.
 */
class AnalyticsController extends Controller
{
    public function __construct(private readonly Geolocator $geolocator) {}

    public function index(Request $request): Response
    {
        $report = new AnalyticsReport($period = $this->period($request));

        return Inertia::render('admin/analytics/index', [
            'period' => [
                'key' => $period->key,
                'label' => $period->label,
                'from' => $period->from->toDateString(),
                'to' => $period->to->toDateString(),
                'granularity' => $period->granularity,
            ],
            'periods' => Period::options(),
            'summary' => $report->summary(),
            'previous' => $report->previousSummary(),
            'series' => $report->series(),
            'topPages' => $report->topPages(),
            'topProducts' => $report->topProducts(),
            'topSearches' => $report->topSearches(),
            'countries' => $report->breakdown('country', codeColumn: 'country_code'),
            'cities' => $report->breakdown('city'),
            'continents' => $report->breakdown('continent'),
            'devices' => $report->breakdown('device', limit: 5),
            'platforms' => $report->breakdown('platform', limit: 8),
            'browsers' => $report->breakdown('browser', limit: 8),
            'sources' => $report->breakdown('source', limit: 5),
            'referrers' => $report->referrers(),
            'hours' => $report->byHour(),
            'weekdays' => $report->byWeekday(),
            'actions' => $report->actions(),
            'funnel' => $report->funnel(),
            'live' => $report->live(),
            // Affiché en pied de page : sans quoi un pays manquant partout
            // ressemble à un bug alors que c'est une clé qui manque.
            'geoDriver' => $this->geolocator->driver(),
        ]);
    }

    /**
     * Activité des trente dernières minutes, rafraîchie sans recharger.
     */
    public function live(): JsonResponse
    {
        $report = new AnalyticsReport(Period::make('today'));

        return response()->json($report->live());
    }

    /**
     * Export du détail journalier, pour qui veut ses propres calculs.
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $this->period($request);
        $rows = (new AnalyticsReport($period))->series();
        $name = 'audience-'.$period->from->toDateString().'-'.$period->to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            // Signature UTF-8 et point-virgule : sans elles, Excel en version
            // française affiche les accents de travers et tout sur une colonne.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Période', 'Visiteurs', 'Pages vues', 'Commandes', 'Chiffre d’affaires'], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    $row['visitors'],
                    $row['pageViews'],
                    $row['orders'],
                    $row['revenue'],
                ], ';');
            }

            fclose($handle);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Le même rapport que le tableau de bord, en PDF imprimable.
     */
    public function pdf(Request $request, AnalyticsPdfService $pdf): HttpResponse
    {
        $period = $this->period($request);
        $report = new AnalyticsReport($period);

        return $pdf->forPeriod($period, $report, $this->geolocator->driver())
            ->download(AnalyticsPdfService::filename($period));
    }

    private function period(Request $request): Period
    {
        $first = Event::min('occurred_at');

        return Period::make(
            $request->query('periode'),
            $first === null ? null : Carbon::parse($first),
        );
    }
}
