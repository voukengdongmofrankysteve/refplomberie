<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\StoreSetting;
use App\Models\Story;
use App\Models\Supplier;
use App\Models\Technician;
use App\Models\TechnicianRequest;
use App\Models\Testimonial;
use App\Models\User;

/**
 * Traduit un type de modèle audité en libellé lisible, et — quand une page
 * s'y prête — en lien vers sa fiche dans le back-office.
 *
 * Un seul endroit à mettre à jour quand un nouveau modèle rejoint le journal
 * d'audit, plutôt que de laisser ce mappage se disperser.
 */
class AuditSubjects
{
    /**
     * @return array{label: string, route: (callable(int): string)|null}
     */
    public static function describe(string $auditableType): array
    {
        return match ($auditableType) {
            Product::class => ['label' => 'Produit', 'route' => fn (int $id): string => route('admin.products.edit', $id)],
            Order::class => ['label' => 'Commande', 'route' => fn (int $id): string => route('admin.orders.show', $id)],
            Quote::class => ['label' => 'Devis', 'route' => fn (): string => route('admin.quotes.index')],
            PromoCode::class => ['label' => 'Code promo', 'route' => fn (): string => route('admin.promo-codes.index')],
            Technician::class => ['label' => 'Technicien', 'route' => fn (): string => route('admin.technicians.index')],
            TechnicianRequest::class => ['label' => 'Intervention', 'route' => fn (int $id): string => route('admin.technician-requests.show', $id)],
            ContactMessage::class => ['label' => 'Message', 'route' => fn (): string => route('admin.messages.index')],
            Story::class => ['label' => 'Statut', 'route' => fn (): string => route('admin.stories.index')],
            Testimonial::class => ['label' => 'Témoignage', 'route' => fn (): string => route('admin.testimonials.index')],
            StoreSetting::class => ['label' => 'Réglages boutique', 'route' => fn (): string => route('admin.settings.edit')],
            Campaign::class => ['label' => 'Campagne', 'route' => fn (): string => route('admin.campaigns.index')],
            Faq::class => ['label' => 'Question FAQ', 'route' => fn (): string => route('admin.faqs.index')],
            FlashSale::class => ['label' => 'Vente flash', 'route' => fn (int $id): string => route('admin.flash-sales.show', $id)],
            User::class => ['label' => 'Compte', 'route' => fn (): string => route('admin.customers.index')],
            Supplier::class => ['label' => 'Fournisseur', 'route' => fn (): string => route('admin.suppliers.index')],
            PurchaseOrder::class => ['label' => 'Bon de commande', 'route' => fn (int $id): string => route('admin.purchase-orders.show', $id)],
            default => ['label' => class_basename($auditableType), 'route' => null],
        };
    }
}
