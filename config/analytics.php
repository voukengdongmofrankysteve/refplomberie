<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mesure d'audience
    |--------------------------------------------------------------------------
    |
    | Tout est enregistré chez nous : aucune donnée ne part chez un tiers, et
    | aucun cookie publicitaire n'est déposé. Le seul cookie posé identifie le
    | navigateur pour distinguer un visiteur qui revient d'un nouveau venu.
    |
    */

    'enabled' => env('ANALYTICS_ENABLED', true),

    /**
     * Fuseau des rapports. Les horodatages sont rangés en temps universel ;
     * le responsable, lui, veut voir ses journées telles qu'il les vit.
     */
    'timezone' => env('ANALYTICS_TIMEZONE', 'Africa/Douala'),

    /** Nom et durée de vie (jours) du cookie de visiteur. */
    'cookie' => env('ANALYTICS_COOKIE', 'rp_visiteur'),
    'cookie_days' => (int) env('ANALYTICS_COOKIE_DAYS', 400),

    /**
     * Minutes d'inactivité au bout desquelles un retour compte comme une
     * nouvelle visite. Trente minutes est la convention du secteur.
     */
    'session_minutes' => (int) env('ANALYTICS_SESSION_MINUTES', 30),

    /** Chemins jamais mesurés : back-office, sondes et fichiers techniques. */
    'ignore' => [
        'admin',
        'admin/*',
        'up',
        'robots.txt',
        'sitemap.xml',
        'storage/*',
        'build/*',
        'mesure',
        'api/v1/mesure',
    ],

    /** Purge automatique : au-delà, les événements détaillés sont effacés. */
    'retention_days' => (int) env('ANALYTICS_RETENTION_DAYS', 730),

    /*
    |--------------------------------------------------------------------------
    | Localisation des visiteurs
    |--------------------------------------------------------------------------
    |
    | Deux pilotes, du plus fiable au plus simple :
    |
    |  - « maxmind » lit une base GeoLite2-City.mmdb posée dans storage/app/geoip.
    |    Gratuite (compte MaxMind requis), hors ligne, sans limite de débit ;
    |    demande d'afficher la mention « This product includes GeoLite2 data
    |    created by MaxMind ». Nécessite le paquet geoip2/geoip2.
    |  - « http » interroge ip-api.com, sans inscription ni clé.
    |
    | Dans les deux cas chaque adresse n'est résolue qu'une fois : le résultat
    | est conservé dans analytics_ip_locations. Le pilote « auto » prend la base
    | locale si elle existe, l'API sinon.
    |
    */

    'geo' => [
        'driver' => env('ANALYTICS_GEO_DRIVER', 'auto'),
        'database' => env(
            'ANALYTICS_GEO_DATABASE',
            storage_path('app/geoip/GeoLite2-City.mmdb'),
        ),
        'endpoint' => env('ANALYTICS_GEO_ENDPOINT', 'http://ip-api.com/json'),
        'timeout' => (int) env('ANALYTICS_GEO_TIMEOUT', 4),
    ],

];
