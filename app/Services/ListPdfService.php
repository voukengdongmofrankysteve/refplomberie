<?php

namespace App\Services;

use App\Models\StoreSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;

/**
 * Rendu PDF d'une liste du back-office — produits, commandes, devis, codes
 * promo, comptes.
 *
 * Un seul gabarit pour les cinq : la mise en page d'un tableau ne change pas
 * d'un écran à l'autre, seuls le titre et les colonnes diffèrent. Même style
 * que les devis (resources/views/pdf/quote.blade.php), pour qu'un document
 * sorti du back-office se reconnaisse au premier coup d'œil.
 */
class ListPdfService
{
    /**
     * @param  array<int, array{label: string, align?: 'left'|'right'}>  $columns
     * @param  array<int, array<int, string>>  $rows  Une ligne = une valeur déjà mise en forme par colonne, dans le même ordre que $columns.
     */
    public function render(
        string $title,
        ?string $subtitle,
        array $columns,
        array $rows,
        ?string $note = null,
        string $orientation = 'portrait',
    ): PdfWrapper {
        return Pdf::loadView('pdf.list', [
            'store' => StoreSetting::current(),
            'watermark' => config('shop.watermark'),
            'title' => $title,
            'subtitle' => $subtitle,
            'columns' => $columns,
            'rows' => $rows,
            'note' => $note,
            'generatedAt' => now(),
        ])->setPaper('a4', $orientation);
    }

    /** Nom de fichier proposé au téléchargement, daté du jour. */
    public static function filename(string $slug): string
    {
        return $slug.'-'.now()->format('Y-m-d').'.pdf';
    }
}
