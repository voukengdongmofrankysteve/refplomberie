<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Accounting\AccountingReport;
use App\Services\Analytics\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tableau de bord comptable : chiffre d'affaires, achats et marge, avec un
 * export en journal comptable générique.
 */
class AccountingController extends Controller
{
    public function index(Request $request): Response
    {
        $report = new AccountingReport($period = $this->period($request));

        return Inertia::render('admin/accounting/index', [
            'period' => [
                'key' => $period->key,
                'label' => $period->label,
                'from' => $period->from->toDateString(),
                'to' => $period->to->toDateString(),
            ],
            'periods' => Period::options(),
            'summary' => $report->summary(),
            'series' => $report->series(),
            'ledger' => $report->ledger(),
        ]);
    }

    /**
     * Journal comptable de la période, au format qu'importe la plupart des
     * logiciels de comptabilité (une ligne, un débit ou un crédit).
     */
    public function export(Request $request): StreamedResponse
    {
        $period = $this->period($request);
        $rows = (new AccountingReport($period))->ledger();
        $name = 'comptabilite-'.$period->from->toDateString().'-'.$period->to->toDateString().'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Date', 'Journal', 'Pièce', 'Tiers', 'Libellé', 'Débit', 'Crédit'], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['journal'],
                    $row['reference'],
                    $row['party'],
                    $row['label'],
                    $row['debit'],
                    $row['credit'],
                ], ';');
            }

            fclose($handle);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(Request $request): Period
    {
        $first = Order::min('created_at');

        return Period::make(
            $request->query('periode'),
            $first === null ? null : Carbon::parse($first),
        );
    }
}
