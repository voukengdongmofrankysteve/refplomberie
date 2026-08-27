<?php

namespace App\Http\Middleware;

use App\Http\Resources\ProductResource;
use App\Models\StoreSetting;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'isAdmin' => $user?->isAdmin() ?? false,
                'isStaff' => $user?->isStaff() ?? false,
                'permissions' => $user?->permissionValues() ?? [],
            ],
            // Closure volontaire : `share()` est appelé avant le contrôleur,
            // le titre ne serait pas encore renseigné s'il était résolu ici.
            'seoTitle' => fn (): string => app(Seo::class)->documentTitle(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Coordonnées, carte et règles de livraison : éditées depuis le
            // back-office, utilisées par la nav, le panier, le contact, la
            // carte et le pied de page.
            'store' => StoreSetting::current()->toSharedArray(),
            // Favoris du compte connecté : la vitrine colore les cœurs et
            // remplit le panneau latéral sans requête supplémentaire.
            'favorites' => $user
                ? ProductResource::collection(
                    $user->favorites()->with(['category', 'images', 'priceTiers'])->get(),
                )->resolve()
                : [],
            // Sans identifiants OAuth, le bouton « continuer avec Google »
            // ne s'affiche pas : proposer un bouton qui mène à une erreur
            // serait pire que ne rien proposer.
            'googleEnabled' => filled(config('services.google.client_id'))
                && filled(config('services.google.client_secret')),
            // Configuration Firebase du web : sans clé VAPID le navigateur ne
            // peut pas s'abonner, et le front s'abstient proprement.
            'firebase' => [
                'apiKey' => config('services.firebase.web.api_key'),
                'authDomain' => config('services.firebase.web.auth_domain'),
                'projectId' => config('services.firebase.project_id'),
                'storageBucket' => config('services.firebase.web.storage_bucket'),
                'messagingSenderId' => config('services.firebase.web.sender_id'),
                'appId' => config('services.firebase.web.app_id'),
                'vapidKey' => config('services.firebase.vapid_key'),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                // Référence de la commande qui vient d'être enregistrée :
                // le panier l'insère dans le message WhatsApp.
                'orderReference' => $request->session()->get('orderReference'),
                // Devis fraîchement établi : le panier propose aussitôt son
                // téléchargement, jeton compris.
                'quoteReference' => $request->session()->get('quoteReference'),
                'quoteUrl' => $request->session()->get('quoteUrl'),
                // Détail des lignes rejetées par l'import du catalogue.
                'importErrors' => $request->session()->get('importErrors', []),
            ],
        ];
    }
}
