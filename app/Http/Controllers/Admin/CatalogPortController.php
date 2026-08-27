<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Export et réimport du catalogue : la passe de mise à jour des prix se fait
 * dans un tableur, pas fiche par fiche.
 */
class CatalogPortController extends Controller
{
    public function __construct(private readonly CatalogCsvService $csv) {}

    public function index(): InertiaResponse
    {
        return Inertia::render('admin/catalog/port', [
            'productsCount' => Product::count(),
        ]);
    }

    public function export(): Response
    {
        return response($this->csv->export(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'
                .CatalogCsvService::exportFilename().'"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ], attributes: ['file' => 'fichier']);

        $report = $this->csv->import($request->file('file'));

        $message = $report['updated'] > 0
            ? "{$report['updated']} produit(s) mis à jour."
            : 'Aucun produit mis à jour.';

        if ($report['skipped'] > 0) {
            $message .= " {$report['skipped']} ligne(s) ignorée(s).";
        }

        return back()
            ->with($report['updated'] > 0 ? 'success' : 'error', $message)
            ->with('importErrors', $report['errors']);
    }
}
