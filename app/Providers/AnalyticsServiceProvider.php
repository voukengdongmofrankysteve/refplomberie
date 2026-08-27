<?php

namespace App\Providers;

use App\Enums\AnalyticsEvent;
use App\Facades\Analytics;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Quote;
use App\Models\Review;
use App\Models\TechnicianRequest;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Branche la mesure sur les événements de la boutique.
 *
 * Écouter les modèles plutôt que d'appeler la mesure depuis les contrôleurs
 * évite d'avoir à instrumenter deux fois chaque action : le site et
 * l'application mobile créent les mêmes objets par des chemins différents,
 * mais une commande reste une commande.
 *
 * Les créations hors requête HTTP — semis, tests, import — ne produisent rien :
 * le service ne trouve alors aucun visiteur à qui rattacher l'action.
 */
class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Order::created(fn (Order $order) => Analytics::record(
            AnalyticsEvent::OrderPlaced,
            subject: $order,
            label: $order->reference,
            value: $order->total,
            meta: ['promo' => $order->promo_code],
        ));

        Quote::created(fn (Quote $quote) => Analytics::record(
            AnalyticsEvent::QuoteRequested,
            subject: $quote,
            label: $quote->reference,
            value: $quote->total,
        ));

        TechnicianRequest::created(fn (TechnicianRequest $request) => Analytics::record(
            AnalyticsEvent::TechnicianRequested,
            subject: $request,
            label: $request->service,
        ));

        ContactMessage::created(fn (ContactMessage $message) => Analytics::record(
            AnalyticsEvent::ContactMessage,
            subject: $message,
            label: $message->subject,
        ));

        Review::created(fn (Review $review) => Analytics::record(
            AnalyticsEvent::ReviewPosted,
            subject: $review->product,
            value: $review->rating,
        ));

        User::created(fn (User $user) => Analytics::record(
            AnalyticsEvent::Registered,
            subject: $user,
        ));

        // La connexion passe par Fortify, par un lien de vérification ou par
        // un jeton d'API : l'événement d'authentification les couvre tous.
        Event::listen(Login::class, fn (Login $event) => Analytics::record(
            AnalyticsEvent::SignedIn,
            subject: $event->user instanceof User ? $event->user : null,
        ));
    }
}
