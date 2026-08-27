<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Coordonnées du magasin
    |--------------------------------------------------------------------------
    |
    | Partagées à toutes les pages Inertia par HandleInertiaRequests, puis
    | affichées par la barre de navigation, la carte, le contact et le footer.
    |
    */

    'store' => [
        'name' => env('SHOP_NAME', 'Réf. Plomberie — Yaoundé'),
        'address' => env('SHOP_ADDRESS', 'Dernier poteau minboman'),
        'phone' => env('SHOP_PHONE', '+237 690 497 379'),
        'whatsapp' => env('SHOP_WHATSAPP', '237690497379'),
        'email' => env('SHOP_EMAIL', 'winorg68@gmail.com'),
        'hours' => env('SHOP_HOURS', 'Lun–Sam : 7h – 18h'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Filigrane des images produit
    |--------------------------------------------------------------------------
    |
    | Incrusté par ProductImageService sur chaque image téléversée depuis le
    | back-office. La police utilisée est resources/fonts/Outfit-Variable.ttf.
    |
    */

    'watermark' => [
        'title' => env('SHOP_WATERMARK_TITLE', 'Réf.Plomberie'),
        'baseline' => env('SHOP_WATERMARK_BASELINE', 'Matériaux & Équipements'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compte administrateur
    |--------------------------------------------------------------------------
    |
    | Utilisé par AdminUserSeeder. Le back-office n'a pas d'inscription :
    | l'unique compte administrateur est créé ici puis se connecte par le
    | formulaire de connexion classique.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur'),
        'email' => env('ADMIN_EMAIL', 'admin@refplomberie.cm'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Livraison
    |--------------------------------------------------------------------------
    |
    | Montants en francs CFA. Le serveur s'en sert pour calculer le total d'une
    | commande ; le panier React affiche la même règle à partir de ces valeurs.
    |
    */

    'shipping' => [
        'cost' => (int) env('SHOP_SHIPPING_COST', 3500),
        'free_from' => (int) env('SHOP_FREE_SHIPPING_FROM', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Devis
    |--------------------------------------------------------------------------
    |
    | Durée d'engagement d'un devis, en jours. Passé ce délai le document est
    | marqué expiré et les prix peuvent être révisés.
    |
    */

    'quotes' => [
        'validity_days' => (int) env('SHOP_QUOTE_VALIDITY_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Services proposés
    |--------------------------------------------------------------------------
    |
    | Liste déroulante du formulaire de demande d'intervention.
    |
    */

    'services' => [
        'Installation plomberie',
        'Dépannage urgence',
        'Diagnostic & Devis',
        'Entretien & Maintenance',
        'Pose de chauffe-eau',
        'Rénovation salle de bain',
        'Autre',
    ],

];
