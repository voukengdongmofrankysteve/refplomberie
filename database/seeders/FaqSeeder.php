<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Questions fréquentes de départ, couvrant les sujets qui reviennent le plus
 * sur une boutique en ligne de matériel plomberie : livraison, paiement,
 * garantie, retours, compte client et services techniciens.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'Livraison',
                'question' => 'Quels sont les délais de livraison ?',
                'answer' => 'Comptez généralement 48h pour Yaoundé et Douala, un peu plus pour les autres villes. Vous recevez le délai estimé directement sur WhatsApp dès la confirmation de votre commande.',
            ],
            [
                'category' => 'Livraison',
                'question' => 'Combien coûte la livraison ?',
                'answer' => 'La livraison standard coûte 3 500 FCFA, et elle est offerte dès 50 000 FCFA d’achat.',
            ],
            [
                'category' => 'Paiement',
                'question' => 'Quels moyens de paiement acceptez-vous ?',
                'answer' => 'Orange Money, MTN Mobile Money, ou espèces à la livraison. Le règlement se fait une fois votre commande confirmée avec notre équipe sur WhatsApp.',
            ],
            [
                'category' => 'Commande',
                'question' => 'Comment passer une commande ?',
                'answer' => 'Ajoutez vos produits au panier puis cliquez sur « Confirmer via WhatsApp » : le récapitulatif complet — articles, quantités, total — est automatiquement formaté et envoyé à notre équipe, qui valide sous quelques minutes.',
            ],
            [
                'category' => 'Commande',
                'question' => 'Puis-je suivre ma commande ?',
                'answer' => 'Oui, connectez-vous à votre compte et rendez-vous dans « Mes commandes » pour suivre son statut en temps réel, de la confirmation à la livraison.',
            ],
            [
                'category' => 'Retours',
                'question' => 'Quelle est votre politique de retour ?',
                'answer' => 'Les retours sont acceptés sous 30 jours, produit non utilisé et dans son emballage d’origine. Contactez-nous sur WhatsApp pour organiser la reprise.',
            ],
            [
                'category' => 'Garantie',
                'question' => 'Vos produits sont-ils garantis ?',
                'answer' => 'Oui, tous nos produits sont garantis au minimum 2 ans. Certains portent en plus un badge de garantie fabricant ou boutique, visible directement sur leur fiche.',
            ],
            [
                'category' => 'Garantie',
                'question' => 'Comment savoir si un produit est authentique ?',
                'answer' => 'Les produits certifiés authentiques ou couverts par une garantie fabricant portent un badge dédié sur leur fiche. Nous ne vendons que du matériel certifié conforme aux normes en vigueur — en cas de doute, contactez-nous.',
            ],
            [
                'category' => 'Compte',
                'question' => 'Dois-je créer un compte pour commander ?',
                'answer' => 'Non, vous pouvez commander en tant qu’invité. Créer un compte vous permet en plus de suivre vos commandes, gérer vos favoris et laisser des avis sur les produits achetés.',
            ],
            [
                'category' => 'Techniciens',
                'question' => 'Proposez-vous un service d’installation ou de dépannage ?',
                'answer' => 'Oui, nos techniciens certifiés interviennent partout au Cameroun pour l’installation, le dépannage et l’entretien. Réservez directement depuis la section « Techniciens » du site.',
            ],
            [
                'category' => 'Techniciens',
                'question' => 'En combien de temps un technicien peut-il intervenir ?',
                'answer' => 'Pour une urgence, nos techniciens interviennent généralement sous 24h selon votre zone et leur disponibilité.',
            ],
            [
                'category' => 'Produits',
                'question' => 'Proposez-vous des tarifs dégressifs pour les professionnels ?',
                'answer' => 'Oui, nos paliers de prix dégressifs s’appliquent automatiquement dès que vous augmentez la quantité d’un même produit dans votre panier.',
            ],
            [
                'category' => 'Avis',
                'question' => 'Qui peut laisser un avis sur un produit ?',
                'answer' => 'Seuls les clients ayant réellement acheté un produit, avec une commande confirmée, peuvent y laisser un avis — ce qui garantit des avis fiables sur chaque fiche produit.',
            ],
            [
                'category' => 'Contact',
                'question' => 'Comment vous contacter en cas de question urgente ?',
                'answer' => 'Le plus rapide est WhatsApp — via le bouton en bas de l’écran ou notre assistant en ligne. Vous pouvez aussi nous appeler ou passer directement en magasin à Yaoundé.',
            ],
        ];

        foreach ($faqs as $position => $entry) {
            Faq::updateOrCreate(
                ['question' => $entry['question']],
                [
                    'answer' => $entry['answer'],
                    'category' => $entry['category'],
                    'position' => $position,
                    'is_active' => true,
                ],
            );
        }
    }
}
