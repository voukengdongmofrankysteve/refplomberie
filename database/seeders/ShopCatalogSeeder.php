<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

                if ($stored === []) {
                    $this->command?->warn("Aucune photo pour {$entry['slug']}");

                    continue;
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
            '%d produits · %d photos filigranées · %d déjà en place.',
            count($catalog),
            $processed,
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
}
