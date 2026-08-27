<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Export et réimport du catalogue au format CSV.
 *
 * Le séparateur est le point-virgule et le fichier porte un BOM UTF-8 : c'est
 * ce qu'attend Excel en configuration française, où un double-clic ouvre alors
 * le fichier avec les accents intacts et les colonnes séparées.
 *
 * L'import met à jour des produits existants, repérés par leur identifiant
 * URL (`slug`). Il ne crée jamais de produit : une fiche sans image ni
 * description soignée n'a pas sa place dans la vitrine, et ces deux éléments
 * se travaillent depuis le formulaire, pas depuis un tableur.
 */
class CatalogCsvService
{
    private const SEPARATOR = ';';

    private const BOM = "\xEF\xBB\xBF";

    /** Colonnes du fichier, dans l'ordre. */
    private const COLUMNS = [
        'slug',
        'nom',
        'categorie',
        'prix',
        'ancien_prix',
        'stock',
        'seuil_alerte',
        'badge',
        'actif',
        'description',
    ];

    /** Colonnes modifiables par l'import. */
    private const EDITABLE = [
        'nom' => 'name',
        'categorie' => 'category_id',
        'prix' => 'price',
        'ancien_prix' => 'old_price',
        'stock' => 'stock',
        'seuil_alerte' => 'low_stock_threshold',
        'badge' => 'badge',
        'actif' => 'is_active',
        'description' => 'description',
    ];

    /** Contenu complet du fichier d'export. */
    public function export(): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, self::BOM);
        fputcsv($handle, self::COLUMNS, self::SEPARATOR, escape: '');

        Product::with('category')
            ->orderBy('name')
            ->chunk(200, function ($products) use ($handle): void {
                foreach ($products as $product) {
                    fputcsv($handle, [
                        $product->slug,
                        $product->name,
                        $product->category->slug,
                        $product->price,
                        $product->old_price,
                        $product->stock,
                        $product->low_stock_threshold,
                        $product->badge,
                        $product->is_active ? 'oui' : 'non',
                        $product->description,
                    ], self::SEPARATOR, escape: '');
                }
            });

        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    /** Nom du fichier proposé au téléchargement. */
    public static function exportFilename(): string
    {
        return 'catalogue-'.now()->format('Y-m-d').'.csv';
    }

    /**
     * Applique un fichier au catalogue.
     *
     * Tout se joue dans une transaction : un fichier partiellement fautif ne
     * laisse pas la moitié des prix modifiés.
     *
     * @return array{updated: int, skipped: int, errors: array<int, string>}
     */
    public function import(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        if ($rows === []) {
            return ['updated' => 0, 'skipped' => 0, 'errors' => ['Le fichier est vide.']];
        }

        $categories = Category::pluck('id', 'slug');
        $updated = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rows, $categories, &$updated, &$skipped, &$errors): void {
            foreach ($rows as $number => $row) {
                $slug = trim((string) ($row['slug'] ?? ''));

                if ($slug === '') {
                    $skipped++;
                    $errors[] = "Ligne {$number} : identifiant URL manquant.";

                    continue;
                }

                $product = Product::where('slug', $slug)->first();

                if ($product === null) {
                    $skipped++;
                    $errors[] = "Ligne {$number} : aucun produit « {$slug} » (l’import ne crée pas de fiche).";

                    continue;
                }

                $attributes = $this->attributesFrom($row, $categories, $number, $errors);

                if ($attributes === null) {
                    $skipped++;

                    continue;
                }

                if ($attributes !== []) {
                    $product->update($attributes);
                }

                $updated++;
            }
        });

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            // Au-delà d'une dizaine de lignes fautives, la liste devient
            // illisible : on la borne et on annonce le reste.
            'errors' => $this->trimErrors($errors),
        ];
    }

    /**
     * Traduit une ligne du fichier en attributs Eloquent.
     *
     * Une cellule laissée vide n'écrase rien : seules les colonnes renseignées
     * sont appliquées. Renvoie `null` si la ligne est à rejeter.
     *
     * @param  array<string, string>  $row
     * @param  Collection<string, int>  $categories
     * @param  array<int, string>  $errors
     * @return array<string, mixed>|null
     */
    private function attributesFrom(array $row, $categories, int $number, array &$errors): ?array
    {
        $attributes = [];

        foreach (self::EDITABLE as $column => $attribute) {
            $raw = trim((string) ($row[$column] ?? ''));

            if ($raw === '') {
                continue;
            }

            switch ($column) {
                case 'categorie':
                    if (! $categories->has($raw)) {
                        $errors[] = "Ligne {$number} : catégorie « {$raw} » inconnue.";

                        return null;
                    }

                    $attributes[$attribute] = $categories->get($raw);
                    break;

                case 'prix':
                case 'ancien_prix':
                case 'stock':
                case 'seuil_alerte':
                    $amount = $this->toInteger($raw);

                    if ($amount === null) {
                        $errors[] = "Ligne {$number} : « {$raw} » n’est pas un nombre valable ({$column}).";

                        return null;
                    }

                    $attributes[$attribute] = $amount;
                    break;

                case 'actif':
                    $attributes[$attribute] = in_array(
                        mb_strtolower($raw),
                        ['oui', 'o', '1', 'true', 'vrai', 'x'],
                        strict: true,
                    );
                    break;

                default:
                    $attributes[$attribute] = $raw;
            }
        }

        return $attributes;
    }

    /**
     * Nombre entier tiré d'une cellule Excel : les espaces de milliers, les
     * espaces insécables et un éventuel suffixe « FCFA » sont tolérés.
     */
    private function toInteger(string $value): ?int
    {
        $cleaned = preg_replace('/[^0-9-]/u', '', str_replace("\u{a0}", '', $value));

        if ($cleaned === '' || $cleaned === null || ! ctype_digit(ltrim($cleaned, '-'))) {
            return null;
        }

        $number = (int) $cleaned;

        return $number < 0 ? null : $number;
    }

    /**
     * Lit le fichier en lignes associatives, indexées par leur numéro affiché
     * dans Excel (l'en-tête est la ligne 1).
     *
     * @return array<int, array<string, string>>
     */
    private function readRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $rows = [];
        $header = null;
        $number = 1;

        while (($cells = fgetcsv($handle, 0, self::SEPARATOR, escape: '')) !== false) {
            if ($cells === [null]) {
                $number++;

                continue;
            }

            if ($header === null) {
                $header = array_map(
                    fn ($cell): string => mb_strtolower(trim(str_replace(self::BOM, '', (string) $cell))),
                    $cells,
                );
                $number++;

                continue;
            }

            $cells = array_pad(array_slice($cells, 0, count($header)), count($header), '');
            $rows[$number] = array_combine($header, array_map(
                fn ($cell): string => (string) $cell,
                $cells,
            ));
            $number++;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<int, string>  $errors
     * @return array<int, string>
     */
    private function trimErrors(array $errors): array
    {
        if (count($errors) <= 10) {
            return $errors;
        }

        $remaining = count($errors) - 10;

        return [...array_slice($errors, 0, 10), "… et {$remaining} autre(s) ligne(s) en erreur."];
    }
}
