<?php

namespace App\Enums;

/**
 * Actions mesurées sur le site et dans l'application.
 *
 * La valeur est écrite en base : la renommer ferait disparaître l'historique
 * des statistiques. On ajoute, on ne réécrit pas.
 */
enum AnalyticsEvent: string
{
    case PageView = 'page_view';
    case ProductView = 'product_view';
    case Search = 'search';
    case AddToCart = 'add_to_cart';
    case CheckoutStarted = 'checkout_started';
    case OrderPlaced = 'order_placed';
    case QuoteRequested = 'quote_requested';
    case TechnicianRequested = 'technician_requested';
    case ContactMessage = 'contact_message';
    case WhatsAppClick = 'whatsapp_click';
    case PhoneClick = 'phone_click';
    case PromoApplied = 'promo_applied';
    case FavoriteAdded = 'favorite_added';
    case ReviewPosted = 'review_posted';
    case StoryView = 'story_view';
    case Registered = 'registered';
    case SignedIn = 'signed_in';

    public function label(): string
    {
        return match ($this) {
            self::PageView => 'Page vue',
            self::ProductView => 'Fiche produit consultée',
            self::Search => 'Recherche',
            self::AddToCart => 'Ajout au panier',
            self::CheckoutStarted => 'Commande entamée',
            self::OrderPlaced => 'Commande passée',
            self::QuoteRequested => 'Devis demandé',
            self::TechnicianRequested => 'Intervention demandée',
            self::ContactMessage => 'Message de contact',
            self::WhatsAppClick => 'Contact WhatsApp',
            self::PhoneClick => 'Appel téléphonique',
            self::PromoApplied => 'Code promo appliqué',
            self::FavoriteAdded => 'Produit mis en favori',
            self::ReviewPosted => 'Avis déposé',
            self::StoryView => 'Statut regardé',
            self::Registered => 'Inscription',
            self::SignedIn => 'Connexion',
        };
    }

    /**
     * Événements que le navigateur a le droit de déclarer lui-même.
     *
     * Tout le reste est écrit par le serveur : accepter une « commande
     * passée » venue du client permettrait de gonfler les chiffres depuis la
     * console du navigateur.
     *
     * @return array<int, string>
     */
    public static function clientReportable(): array
    {
        return [
            self::AddToCart->value,
            self::CheckoutStarted->value,
            self::WhatsAppClick->value,
            self::PhoneClick->value,
            self::StoryView->value,
            self::ProductView->value,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
