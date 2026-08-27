<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    /**
     * Initialise l'unique ligne de réglages depuis `config/shop.php`.
     *
     * Ré-exécutable : si la boutique a déjà été configurée depuis le
     * back-office, ses valeurs sont conservées.
     */
    public function run(): void
    {
        if (StoreSetting::query()->exists()) {
            return;
        }

        StoreSetting::create([
            ...config('shop.store'),
            // Avenue Kennedy, Yaoundé — point de départ ajustable dans l'admin.
            'latitude' => 3.8666,
            'longitude' => 11.5167,
            'map_zoom' => 15,
            'meta_title' => 'Réf. Plomberie — Matériaux & Équipements au Cameroun',
            'meta_description' => 'Robinetterie, tuyauterie, sanitaire et outillage professionnel. '
                .'Commandez en ligne, confirmez via WhatsApp, livraison rapide partout au Cameroun.',
            'meta_keywords' => 'plomberie, robinetterie, tuyauterie, sanitaire, chauffe-eau, '
                .'outillage, Cameroun, Yaoundé, plombier',
            'is_indexable' => true,
        ]);

        StoreSetting::forgetCurrent();
    }
}
