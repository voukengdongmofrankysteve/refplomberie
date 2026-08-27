<?php

use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\TechnicianRequestController;
use App\Http\Controllers\NotificationCenterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de l'application mobile
|--------------------------------------------------------------------------
|
| Côté client uniquement. L'administration reste sur le web, derrière une
| session : aucune route de back-office n'est exposée ici, de sorte qu'un
| jeton dérobé sur un téléphone ne puisse jamais ouvrir la gestion.
|
| Toutes les routes sont préfixées par /api/v1.
|
*/

Route::prefix('v1')->group(function (): void {

    /*
    |----------------------------------------------------------------------
    | Accès libre
    |----------------------------------------------------------------------
    */

    Route::get('boutique', [CatalogController::class, 'bootstrap'])->name('api.bootstrap');
    Route::get('produits', [CatalogController::class, 'index'])->name('api.products.index');
    Route::get('recherche', [CatalogController::class, 'search'])->name('api.search');
    Route::get('stories', [CatalogController::class, 'stories'])->name('api.stories');
    Route::get('techniciens', [CatalogController::class, 'technicians'])->name('api.technicians');
    Route::get('faq', [CatalogController::class, 'faqs'])->name('api.faqs');
    // Après les routes fixes : sinon « recherche » serait pris pour un slug.
    Route::get('produits/{product}', [CatalogController::class, 'show'])->name('api.products.show');

    // Mêmes actions « écran » que sur le site : panier, contact, statuts.
    Route::post('mesure', AnalyticsEventController::class)
        ->middleware('throttle:60,1')
        ->name('api.analytics.record');

    Route::post('inscription', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('api.register');
    Route::post('connexion', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('api.login');
    // Connexion et inscription confondues : Google ne les distingue pas, et
    // le client non plus.
    Route::post('connexion/google', [AuthController::class, 'google'])
        ->middleware('throttle:10,1')
        ->name('api.login.google');

    // Commande, devis, intervention et contact restent ouverts aux visiteurs :
    // au Cameroun la plupart des clients commandent sans créer de compte.
    Route::post('commandes', [OrderController::class, 'store'])->name('api.orders.store');
    Route::post('devis', [QuoteController::class, 'store'])->name('api.quotes.store');
    Route::post('interventions', [TechnicianRequestController::class, 'store'])
        ->name('api.technician-requests.store');
    Route::post('messages', [ContactMessageController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.messages.store');
    // Vérification en lecture seule, ouverte : un visiteur non connecté doit
    // pouvoir appliquer son code avant de commander.
    Route::get('code-promo', [AccountController::class, 'checkPromoCode'])
        ->name('api.promo-codes.check');

    /*
    |----------------------------------------------------------------------
    | Client authentifié
    |----------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('moi', [AuthController::class, 'me'])->name('api.me');
        Route::put('moi', [AuthController::class, 'update'])->name('api.me.update');
        Route::post('deconnexion', [AuthController::class, 'logout'])->name('api.logout');

        Route::get('mes-commandes', [OrderController::class, 'index'])->name('api.orders.index');
        Route::get('mes-commandes/{order}', [OrderController::class, 'show'])
            ->name('api.orders.show');
        Route::get('mes-devis', [QuoteController::class, 'index'])->name('api.quotes.index');
        Route::get('mes-interventions', [TechnicianRequestController::class, 'index'])
            ->name('api.technician-requests.index');

        Route::get('favoris', [AccountController::class, 'favorites'])->name('api.favorites');
        Route::post('favoris/{product}', [AccountController::class, 'toggleFavorite'])
            ->name('api.favorites.toggle');

        Route::post('produits/{product}/avis', [AccountController::class, 'storeReview'])
            ->name('api.reviews.store');

        // Journal et appareils : partagés avec le site, mêmes réponses.
        Route::get('notifications/journal', [NotificationCenterController::class, 'index'])
            ->name('api.notifications.index');
        Route::post('notifications/journal/lu', [NotificationCenterController::class, 'markRead'])
            ->name('api.notifications.read-all');
        Route::post('notifications/journal/{notification}/lu', [NotificationCenterController::class, 'markRead'])
            ->name('api.notifications.read');
        Route::post('notifications/appareil', [NotificationCenterController::class, 'registerDevice'])
            ->name('api.notifications.device.register');
        Route::delete('notifications/appareil', [NotificationCenterController::class, 'forgetDevice'])
            ->name('api.notifications.device.forget');

        Route::post('notifications/code', [AccountController::class, 'sendEmailCode'])
            ->middleware('throttle:6,1')
            ->name('api.notifications.code');
        Route::post('notifications/confirmer', [AccountController::class, 'confirmEmailCode'])
            ->middleware('throttle:10,1')
            ->name('api.notifications.confirm');
        Route::put('notifications', [AccountController::class, 'updateNotifications'])
            ->name('api.notifications.update');
        Route::delete('notifications', [AccountController::class, 'disableNotifications'])
            ->name('api.notifications.destroy');
    });
});
