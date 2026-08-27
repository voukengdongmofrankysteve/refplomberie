<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\Auth;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PromoCodeController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TechnicianRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Vitrine publique
|--------------------------------------------------------------------------
*/

Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('produit/{product}', [ShopController::class, 'show'])->name('shop.product');

Route::get('recherche', SearchController::class)->name('search');

/*
|--------------------------------------------------------------------------
| Connexion par compte Google
|--------------------------------------------------------------------------
|
| Un seul parcours pour l'inscription et la connexion : selon que le compte
| existe déjà ou non, le client est retrouvé ou créé, mais il arrive connecté
| dans les deux cas.
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('auth/google', [Auth\GoogleController::class, 'redirect'])
        ->name('auth.google');
    Route::get('auth/google/retour', [Auth\GoogleController::class, 'callback'])
        ->name('auth.google.callback');
});

// Actions qui n'existent que dans le navigateur : ajout au panier, clic
// WhatsApp, statut regardé. Bridée : c'est une route ouverte, et un script
// lâché dessus remplirait la base d'événements inventés.
Route::post('mesure', AnalyticsEventController::class)
    ->middleware('throttle:60,1')
    ->name('analytics.record');

Route::post('commandes', [OrderController::class, 'store'])->name('orders.store');
Route::get('code-promo', [PromoCodeController::class, 'check'])->name('promo-codes.check');

Route::post('devis', [QuoteController::class, 'store'])->name('quotes.store');
// Le jeton fait office d'autorisation : pas de compte à créer pour récupérer
// son propre devis, mais la référence seule n'ouvre rien.
Route::get('devis/{quote}/{token}', [QuoteController::class, 'download'])
    ->name('quotes.download');
Route::post('interventions', [TechnicianRequestController::class, 'store'])
    ->name('technician-requests.store');
Route::post('messages', [ContactMessageController::class, 'store'])
    ->name('contact-messages.store');

/*
|--------------------------------------------------------------------------
| Référencement
|--------------------------------------------------------------------------
*/

Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

/*
|--------------------------------------------------------------------------
| Espace client
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', Account\DashboardController::class)->name('dashboard');
    Route::get('mes-favoris', [Account\FavoriteController::class, 'index'])
        ->name('account.favorites');
    Route::get('mes-commandes', [Account\OrderController::class, 'index'])
        ->name('account.orders');
    Route::get('mes-interventions', [Account\TechnicianRequestController::class, 'index'])
        ->name('account.technician-requests');

    Route::post('favoris/{product}', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    // Centre de notifications : mêmes points d'entrée pour le site et
    // l'application, seule l'authentification diffère.
    Route::get('notifications/journal', [NotificationCenterController::class, 'index'])
        ->name('notifications.index');
    Route::post('notifications/journal/lu', [NotificationCenterController::class, 'markRead'])
        ->name('notifications.read-all');
    Route::post('notifications/journal/{notification}/lu', [NotificationCenterController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('notifications/appareil', [NotificationCenterController::class, 'registerDevice'])
        ->name('notifications.device.register');
    Route::delete('notifications/appareil', [NotificationCenterController::class, 'forgetDevice'])
        ->name('notifications.device.forget');
    Route::post('produit/{product}/avis', [ReviewController::class, 'store'])
        ->name('reviews.store');
});

/*
|--------------------------------------------------------------------------
| Back-office administrateur
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::middleware('permission:analytics')->group(function (): void {
            Route::get('audience', [Admin\AnalyticsController::class, 'index'])
                ->name('analytics.index');
            Route::get('audience/direct', [Admin\AnalyticsController::class, 'live'])
                ->name('analytics.live');
            Route::get('audience/export', [Admin\AnalyticsController::class, 'export'])
                ->name('analytics.export');
            Route::get('audience/pdf', [Admin\AnalyticsController::class, 'pdf'])
                ->name('analytics.pdf');
        });

        Route::middleware('permission:products')->group(function (): void {
            Route::get('products/export/pdf', [Admin\ProductController::class, 'exportPdf'])
                ->name('products.export');
            Route::resource('products', Admin\ProductController::class)
                ->except('show');
        });

        Route::middleware('permission:catalog')->group(function (): void {
            Route::get('catalogue/export', [Admin\CatalogPortController::class, 'export'])
                ->name('catalog.export');
            Route::get('catalogue', [Admin\CatalogPortController::class, 'index'])
                ->name('catalog.index');
            Route::post('catalogue/import', [Admin\CatalogPortController::class, 'import'])
                ->name('catalog.import');
        });

        Route::middleware('permission:orders')->group(function (): void {
            Route::get('orders', [Admin\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/export/pdf', [Admin\OrderController::class, 'exportPdf'])
                ->name('orders.export');
            Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
            Route::put('orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
            Route::get('orders/{order}/pdf', [Admin\OrderController::class, 'pdf'])
                ->name('orders.pdf');
            Route::delete('orders/{order}', [Admin\OrderController::class, 'destroy'])
                ->name('orders.destroy');
        });

        Route::middleware('permission:quotes')->group(function (): void {
            Route::get('quotes', [Admin\QuoteController::class, 'index'])->name('quotes.index');
            Route::get('quotes/export/pdf', [Admin\QuoteController::class, 'exportPdf'])
                ->name('quotes.export');
            Route::get('quotes/{quote}/pdf', [Admin\QuoteController::class, 'pdf'])
                ->name('quotes.pdf');
            Route::post('quotes/{quote}/convertir', [Admin\QuoteController::class, 'convert'])
                ->name('quotes.convert');
            Route::put('quotes/{quote}', [Admin\QuoteController::class, 'update'])
                ->name('quotes.update');
            Route::delete('quotes/{quote}', [Admin\QuoteController::class, 'destroy'])
                ->name('quotes.destroy');
        });

        Route::middleware('permission:campaigns')->group(function (): void {
            Route::get('campaigns', [Admin\CampaignController::class, 'index'])
                ->name('campaigns.index');
            Route::post('campaigns', [Admin\CampaignController::class, 'store'])
                ->name('campaigns.store');
            Route::put('campaigns/{campaign}', [Admin\CampaignController::class, 'update'])
                ->name('campaigns.update');
            Route::post('campaigns/{campaign}/envoyer', [Admin\CampaignController::class, 'send'])
                ->name('campaigns.send');
            Route::delete('campaigns/{campaign}', [Admin\CampaignController::class, 'destroy'])
                ->name('campaigns.destroy');
        });

        Route::middleware('permission:promo-codes')->group(function (): void {
            Route::get('promo-codes', [Admin\PromoCodeController::class, 'index'])
                ->name('promo-codes.index');
            Route::get('promo-codes/export/pdf', [Admin\PromoCodeController::class, 'exportPdf'])
                ->name('promo-codes.export');
            Route::post('promo-codes', [Admin\PromoCodeController::class, 'store'])
                ->name('promo-codes.store');
            Route::put('promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'update'])
                ->name('promo-codes.update');
            Route::delete('promo-codes/{promoCode}', [Admin\PromoCodeController::class, 'destroy'])
                ->name('promo-codes.destroy');
        });

        Route::middleware('permission:accounting')->group(function (): void {
            Route::get('accounting', [Admin\AccountingController::class, 'index'])
                ->name('accounting.index');
            Route::get('accounting/export', [Admin\AccountingController::class, 'export'])
                ->name('accounting.export');
        });

        Route::middleware('permission:audit-log')->group(function (): void {
            Route::get('audit-log', [Admin\AuditLogController::class, 'index'])
                ->name('audit-log.index');
        });

        Route::middleware('permission:faqs')->group(function (): void {
            Route::get('faqs', [Admin\FaqController::class, 'index'])
                ->name('faqs.index');
            Route::post('faqs', [Admin\FaqController::class, 'store'])
                ->name('faqs.store');
            Route::put('faqs/{faq}', [Admin\FaqController::class, 'update'])
                ->name('faqs.update');
            Route::delete('faqs/{faq}', [Admin\FaqController::class, 'destroy'])
                ->name('faqs.destroy');
        });

        Route::middleware('permission:flash-sales')->group(function (): void {
            Route::get('flash-sales', [Admin\FlashSaleController::class, 'index'])
                ->name('flash-sales.index');
            Route::get('flash-sales/{flashSale}', [Admin\FlashSaleController::class, 'show'])
                ->name('flash-sales.show');
            Route::post('flash-sales', [Admin\FlashSaleController::class, 'store'])
                ->name('flash-sales.store');
            Route::put('flash-sales/{flashSale}', [Admin\FlashSaleController::class, 'update'])
                ->name('flash-sales.update');
            Route::delete('flash-sales/{flashSale}', [Admin\FlashSaleController::class, 'destroy'])
                ->name('flash-sales.destroy');

            Route::post('flash-sales/{flashSale}/products', [Admin\FlashSaleProductController::class, 'store'])
                ->name('flash-sales.products.store');
            Route::put('flash-sales/{flashSale}/products/{product}', [Admin\FlashSaleProductController::class, 'update'])
                ->name('flash-sales.products.update');
            Route::delete('flash-sales/{flashSale}/products/{product}', [Admin\FlashSaleProductController::class, 'destroy'])
                ->name('flash-sales.products.destroy');
        });

        Route::middleware('permission:technicians')->group(function (): void {
            Route::get('technicians', [Admin\TechnicianController::class, 'index'])
                ->name('technicians.index');
            Route::post('technicians', [Admin\TechnicianController::class, 'store'])
                ->name('technicians.store');
            Route::put('technicians/{technician}', [Admin\TechnicianController::class, 'update'])
                ->name('technicians.update');
            Route::delete('technicians/{technician}', [Admin\TechnicianController::class, 'destroy'])
                ->name('technicians.destroy');
        });

        Route::middleware('permission:technician-requests')->group(function (): void {
            Route::get('technician-requests', [Admin\TechnicianRequestController::class, 'index'])
                ->name('technician-requests.index');
            Route::get('technician-requests/{technicianRequest}', [Admin\TechnicianRequestController::class, 'show'])
                ->name('technician-requests.show');
            Route::put('technician-requests/{technicianRequest}', [Admin\TechnicianRequestController::class, 'update'])
                ->name('technician-requests.update');
            Route::delete('technician-requests/{technicianRequest}', [Admin\TechnicianRequestController::class, 'destroy'])
                ->name('technician-requests.destroy');
        });

        Route::middleware('permission:messages')->group(function (): void {
            Route::get('messages', [Admin\ContactMessageController::class, 'index'])
                ->name('messages.index');
            Route::put('messages/{message}', [Admin\ContactMessageController::class, 'update'])
                ->name('messages.update');
            Route::delete('messages/{message}', [Admin\ContactMessageController::class, 'destroy'])
                ->name('messages.destroy');
        });

        Route::middleware('permission:stories')->group(function (): void {
            Route::get('stories', [Admin\StoryController::class, 'index'])
                ->name('stories.index');
            Route::post('stories', [Admin\StoryController::class, 'store'])
                ->name('stories.store');
            Route::put('stories/{story}', [Admin\StoryController::class, 'update'])
                ->name('stories.update');
            Route::delete('stories/{story}', [Admin\StoryController::class, 'destroy'])
                ->name('stories.destroy');
        });

        Route::middleware('permission:settings')->group(function (): void {
            Route::get('settings', [Admin\StoreSettingController::class, 'edit'])
                ->name('settings.edit');
            Route::put('settings', [Admin\StoreSettingController::class, 'update'])
                ->name('settings.update');
        });

        Route::middleware('permission:testimonials')->group(function (): void {
            Route::get('testimonials', [Admin\TestimonialController::class, 'index'])
                ->name('testimonials.index');
            Route::post('testimonials', [Admin\TestimonialController::class, 'store'])
                ->name('testimonials.store');
            Route::put('testimonials/{testimonial}', [Admin\TestimonialController::class, 'update'])
                ->name('testimonials.update');
            Route::delete('testimonials/{testimonial}', [Admin\TestimonialController::class, 'destroy'])
                ->name('testimonials.destroy');
        });

        Route::middleware('permission:suppliers')->group(function (): void {
            Route::get('suppliers', [Admin\SupplierController::class, 'index'])
                ->name('suppliers.index');
            Route::post('suppliers', [Admin\SupplierController::class, 'store'])
                ->name('suppliers.store');
            Route::put('suppliers/{supplier}', [Admin\SupplierController::class, 'update'])
                ->name('suppliers.update');
            Route::delete('suppliers/{supplier}', [Admin\SupplierController::class, 'destroy'])
                ->name('suppliers.destroy');

            Route::get('purchase-orders', [Admin\PurchaseOrderController::class, 'index'])
                ->name('purchase-orders.index');
            Route::get('purchase-orders/{purchaseOrder}', [Admin\PurchaseOrderController::class, 'show'])
                ->name('purchase-orders.show');
            Route::post('purchase-orders', [Admin\PurchaseOrderController::class, 'store'])
                ->name('purchase-orders.store');
            Route::put('purchase-orders/{purchaseOrder}', [Admin\PurchaseOrderController::class, 'update'])
                ->name('purchase-orders.update');
            Route::delete('purchase-orders/{purchaseOrder}', [Admin\PurchaseOrderController::class, 'destroy'])
                ->name('purchase-orders.destroy');

            Route::post('purchase-orders/{purchaseOrder}/items', [Admin\PurchaseOrderItemController::class, 'store'])
                ->name('purchase-orders.items.store');
            Route::put('purchase-orders/{purchaseOrder}/items/{item}', [Admin\PurchaseOrderItemController::class, 'update'])
                ->name('purchase-orders.items.update');
            Route::delete('purchase-orders/{purchaseOrder}/items/{item}', [Admin\PurchaseOrderItemController::class, 'destroy'])
                ->name('purchase-orders.items.destroy');
        });

        Route::middleware('permission:accounts')->group(function (): void {
            Route::get('customers', [Admin\CustomerController::class, 'index'])
                ->name('customers.index');
            Route::get('customers/export/pdf', [Admin\CustomerController::class, 'exportPdf'])
                ->name('customers.export');
            Route::put('customers/{user}', [Admin\CustomerController::class, 'update'])
                ->name('customers.update');
        });
    });

require __DIR__.'/settings.php';
