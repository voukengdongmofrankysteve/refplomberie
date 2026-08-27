<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Story;
use Illuminate\Database\Seeder;

/**
 * Amorce le fil de statuts avec quelques arrivages, à partir des photos déjà
 * traitées par ShopCatalogSeeder. Aucune image supplémentaire n'est générée.
 */
class StorySeeder extends Seeder
{
    public function run(): void
    {
        $stories = [
            [
                'slug' => 'evier-inox-double-bac-summit-home',
                'title' => 'Arrivage éviers Summit Home',
                'caption' => 'Double bac inox et noir satiné, en stock au magasin.',
                'link_label' => 'Voir l’évier',
            ],
            [
                'slug' => 'colonne-de-douche-anthracite',
                'title' => 'Colonnes de douche anthracite',
                'caption' => 'Le noir mat qui ne montre pas le calcaire.',
                'link_label' => 'Voir la colonne',
            ],
            [
                'slug' => 'reservoir-surpresseur-vessie',
                'title' => 'Surpresseurs à vessie',
                'caption' => 'Fini les démarrages de pompe à répétition.',
                'link_label' => 'Voir le réservoir',
            ],
            [
                'slug' => 'filtre-eau-industriel-20-pouces',
                'title' => 'Filtration gros débit 20"',
                'caption' => 'Pour un immeuble entier ou un forage.',
                'link_label' => 'Voir le filtre',
            ],
            [
                'slug' => 'lampe-souder-kemper-tornado',
                'title' => 'Outillage pro Kemper',
                'caption' => 'Chalumeau à allumage piézo, prêt à braser.',
                'link_label' => 'Voir la lampe',
            ],
            [
                'slug' => 'pack-wc-lavabo-colonne',
                'title' => 'Pack sanitaire complet',
                'caption' => 'WC, lavabo et colonne d’un seul tenant.',
                'link_label' => 'Voir le pack',
            ],
        ];

        foreach ($stories as $position => $entry) {
            $product = Product::where('slug', $entry['slug'])->first();

            if ($product === null || $product->image === '') {
                continue;
            }

            Story::updateOrCreate(
                ['title' => $entry['title']],
                [
                    'caption' => $entry['caption'],
                    'media_type' => Story::TYPE_IMAGE,
                    // On réutilise la photo produit, déjà filigranée.
                    'media_path' => $product->image,
                    'link_url' => route('shop.product', $product, absolute: false),
                    'link_label' => $entry['link_label'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}
