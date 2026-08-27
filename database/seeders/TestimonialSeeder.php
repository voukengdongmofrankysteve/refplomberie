<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Amorce la section « Ils nous font confiance » avec les témoignages qui
 * servaient jusqu'ici de texte figé sur la page d'accueil — désormais
 * modifiables depuis le back-office.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => 'Marc Dupont',
                'role' => 'Propriétaire, Yaoundé',
                'text' => 'Commande passée le matin, confirmée sur WhatsApp en 5 minutes, livraison le lendemain. Qualité professionnelle à prix accessible. Je recommande vivement !',
                'rating' => 5,
            ],
            [
                'name' => 'Sophie Martin',
                'role' => 'Artisan plombier, Douala',
                'text' => "En tant que professionnelle, j'ai besoin de matériel fiable rapidement. Réf. Plomberie répond à tous mes critères : stock disponible, livraison rapide, et le SAV est au top.",
                'rating' => 5,
            ],
            [
                'name' => 'Jean-Pierre Rousseau',
                'role' => "Gérant d'immeuble, Yaoundé",
                'text' => "Gestion de 12 appartements, donc j'ai souvent des urgences plomberie. Le système de commande WhatsApp est génial, je reçois ma confirmation en quelques minutes.",
                'rating' => 5,
            ],
        ];

        foreach ($testimonials as $position => $entry) {
            Testimonial::updateOrCreate(
                ['name' => $entry['name']],
                [
                    'role' => $entry['role'],
                    'text' => $entry['text'],
                    'rating' => $entry['rating'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}
