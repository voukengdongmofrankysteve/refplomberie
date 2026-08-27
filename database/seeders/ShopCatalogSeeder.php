<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Catalogue réel de la boutique, monté depuis les photos prises en magasin.
 *
 * Chaque image passe par ProductImageService : redimensionnement, conversion
 * WebP et filigrane « Réf.Plomberie ». Le seeder est donc directement
 * exploitable en production — aucune image externe, aucun téléversement manuel.
 *
 *   php artisan db:seed --class=ShopCatalogSeeder
 *
 * Ré-exécutable : les produits sont reconnus à leur slug et leurs photos ne
 * sont retraitées que si la galerie est vide.
 */
class ShopCatalogSeeder extends Seeder
{
    private const PHOTOS = __DIR__.'/data/photos';

    public function __construct(private readonly ProductImageService $images) {}

    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $catalog */
        $catalog = require __DIR__.'/data/shop-catalog.php';

        $processed = 0;
        $skipped = 0;
        $placeholders = 0;

        foreach ($catalog as $entry) {
            $category = Category::where('slug', $entry['category'])->first();

            if ($category === null) {
                $this->command?->warn("Catégorie inconnue : {$entry['category']} ({$entry['slug']})");

                continue;
            }

            $product = Product::firstOrNew(['slug' => $entry['slug']]);

            $product->fill([
                'category_id' => $category->id,
                'name' => $entry['name'],
                'description' => $entry['description'],
                'price' => $entry['price'],
                'old_price' => $entry['old_price'],
                'badge' => $entry['badge'],
                'stock' => $entry['stock'],
                'is_active' => true,
            ]);

            // `image` est obligatoire en base : valeur provisoire à la création,
            // remplacée juste après par la photo traitée. Sur un produit
            // existant, la photo déjà en place est conservée.
            if (! $product->exists) {
                $product->image = '';
            }

            $product->save();

            // Les photos ne sont traitées qu'une fois : relancer le seeder ne
            // regénère pas 48 fichiers WebP inutilement.
            if ($product->images()->exists() && $product->image !== '') {
                $skipped++;
            } else {
                $stored = $this->importPhotos($entry['photos']);

                // Article référencé mais pas encore photographié en magasin :
                // on génère une vignette de marque « photo à venir » plutôt
                // que de le laisser hors catalogue ou d'afficher une image
                // cassée. Le back-office permet d'y substituer la vraie photo.
                if ($stored === []) {
                    $stored = [$this->placeholder($entry['name'])];
                    $placeholders++;
                }

                $product->update(['image' => $stored[0]]);
                $product->images()->delete();

                foreach ($stored as $position => $path) {
                    $product->images()->create(['url' => $path, 'position' => $position]);
                }

                $processed += count($stored);
            }

            $product->priceTiers()->delete();

            foreach ($entry['tiers'] as [$min, $max, $price]) {
                $product->priceTiers()->create([
                    'min_qty' => $min,
                    'max_qty' => $max,
                    'price' => $price,
                ]);
            }
        }

        $this->command?->info(sprintf(
            '%d produits · %d photos filigranées · %d vignettes « photo à venir » · %d déjà en place.',
            count($catalog),
            $processed,
            $placeholders,
            $skipped,
        ));
    }

    /**
     * Optimise et filigrane les photos d'un produit.
     *
     * @param  array<int, string>  $files
     * @return array<int, string>
     */
    private function importPhotos(array $files): array
    {
        $stored = [];

        foreach ($files as $file) {
            $path = self::PHOTOS.'/'.$file;

            if (! is_file($path)) {
                $this->command?->warn("Photo introuvable : {$file}");

                continue;
            }

            // Copie temporaire : UploadedFile déplace le fichier d'origine.
            $temporary = Storage::disk('local')->path('seed-'.basename($file));
            @mkdir(dirname($temporary), 0755, true);
            copy($path, $temporary);

            $stored[] = $this->images->store(
                new UploadedFile($temporary, basename($file), null, null, true),
            );

            @unlink($temporary);
        }

        return $stored;
    }

    /**
     * Vignette de marque pour un article encore non photographié.
     *
     * Volontairement sobre et explicite — « Photo à venir » écrit noir sur
     * gris clair — pour qu'on ne la confonde jamais avec une vraie photo de
     * produit, ni sur la vitrine ni dans le back-office.
     */
    private function placeholder(string $name): string
    {
        $size = 1000;
        $image = imagecreatetruecolor($size, $size);

        $background = (int) imagecolorallocate($image, 248, 249, 250);
        $ink = (int) imagecolorallocate($image, 26, 26, 46);
        $muted = (int) imagecolorallocate($image, 74, 74, 106);
        $green = (int) imagecolorallocate($image, 37, 211, 102);

        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 0, $size - 12, $size, $size, $green);

        $font = resource_path('fonts/Outfit-Variable.ttf');

        if (is_file($font)) {
            // Le nom du produit, replié sur plusieurs lignes centrées.
            $lines = $this->wrap($name, $font, 34.0, $size - 160);
            $y = (int) ($size / 2) - (count($lines) * 26);

            foreach ($lines as $line) {
                $box = imagettfbbox(34.0, 0, $font, $line);
                $width = $box === false ? 0 : $box[2] - $box[0];
                imagettftext($image, 34.0, 0, (int) (($size - $width) / 2), $y, $ink, $font, $line);
                $y += 52;
            }

            foreach ([['Photo à venir', 22.0, $muted, $y + 40], ['Réf. Plomberie', 18.0, $green, $size - 70]] as [$text, $pt, $colour, $baseline]) {
                $box = imagettfbbox($pt, 0, $font, $text);
                $width = $box === false ? 0 : $box[2] - $box[0];
                imagettftext($image, $pt, 0, (int) (($size - $width) / 2), (int) $baseline, $colour, $font, $text);
            }
        }

        $path = 'products/'.Str::uuid()->toString().'.webp';

        ob_start();
        imagewebp($image, null, 82);
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * Découpe un libellé en lignes tenant dans une largeur donnée.
     *
     * @return array<int, string>
     */
    private function wrap(string $text, string $font, float $size, int $maxWidth): array
    {
        $lines = [];
        $current = '';

        foreach (explode(' ', $text) as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $box = imagettfbbox($size, 0, $font, $candidate);
            $width = $box === false ? 0 : $box[2] - $box[0];

            if ($width > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
