<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Catégories, produits, galeries et paliers de prix de démarrage.
     */
    public function run(): void
    {
        $categories = [
            ['slug' => 'robinetterie', 'label' => 'Robinetterie'],
            ['slug' => 'tuyauterie', 'label' => 'Tuyauterie & Raccords'],
            ['slug' => 'sanitaire', 'label' => 'Sanitaire'],
            ['slug' => 'chauffe-eau', 'label' => 'Chauffe-eau'],
            ['slug' => 'outils', 'label' => 'Outils & Accessoires'],
            ['slug' => 'evacuation', 'label' => 'Évacuation & Drainage'],
            ['slug' => 'chauffage', 'label' => 'Chauffage'],
            ['slug' => 'isolation', 'label' => 'Isolation & Étanchéité'],
            ['slug' => 'pompes', 'label' => 'Pompes & Surpresseurs'],
            ['slug' => 'filtration', 'label' => 'Filtration & Traitement de l\'eau'],
            ['slug' => 'ventilation', 'label' => 'Ventilation & VMC'],
            ['slug' => 'electricite', 'label' => 'Électricité & Domotique'],
            ['slug' => 'adoucisseurs', 'label' => 'Adoucisseurs d\'eau'],
            ['slug' => 'compteurs', 'label' => 'Compteurs & Détection de fuites'],
            ['slug' => 'collecteurs', 'label' => 'Collecteurs & Manifolds'],
            ['slug' => 'flexible', 'label' => 'Flexibles & Durites'],
            ['slug' => 'joints-etancheite', 'label' => 'Joints & Produits d\'étanchéité'],
            ['slug' => 'vannes', 'label' => 'Vannes & Clapets'],
            ['slug' => 'wc-urinoirs', 'label' => 'WC & Urinoirs'],
            ['slug' => 'douches', 'label' => 'Douches & Baignoires'],
            ['slug' => 'lavabos', 'label' => 'Lavabos & Éviers'],
            ['slug' => 'fixations', 'label' => 'Fixations & Supports'],
            ['slug' => 'colles-soudures', 'label' => 'Colles, Soudures & Brasures'],
            ['slug' => 'protection-anticorrosion', 'label' => 'Protection & Anticorrosion'],
            ['slug' => 'piscines-spa', 'label' => 'Piscines & Spa'],
            ['slug' => 'arrosage', 'label' => 'Arrosage & Irrigation'],
            ['slug' => 'eclairage', 'label' => 'Éclairage'],
            ['slug' => 'peinture', 'label' => 'Peinture & Décoration'],
            ['slug' => 'quincaillerie', 'label' => 'Quincaillerie & Serrurerie'],
        ];

        foreach ($categories as $position => $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['label' => $category['label'], 'position' => $position],
            );
        }

    }
}
