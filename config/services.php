<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Hostinger Mail API
    |--------------------------------------------------------------------------
    |
    | Le jeton se crée depuis l'onglet e-mail du panneau Hostinger ; il est
    | rattaché à une commande et ne donne accès qu'aux boîtes de celle-ci.
    | Ce n'est PAS le mot de passe de la boîte, qui ne sert qu'au webmail
    | et à l'IMAP/SMTP.
    |
    */

    'hostinger' => [
        'token' => env('HOSTINGER_MAIL_TOKEN'),
        'mailbox' => env('HOSTINGER_MAIL_MAILBOX'),
        'base_url' => env('HOSTINGER_MAIL_BASE_URL', 'https://api.mail.hostinger.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    |
    | `credentials` pointe vers le fichier JSON d'un compte de service, à
    | télécharger depuis la console Firebase (Paramètres du projet → Comptes de
    | service). Ce fichier est un secret : il ne doit jamais être versionné.
    |
    | `vapid_key` sert au web uniquement : c'est la « paire de clés Web Push »
    | de l'onglet Cloud Messaging. Sans elle, le navigateur ne peut pas
    | s'abonner ; l'application mobile n'en a pas besoin.
    |
    */

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
        'web' => [
            'api_key' => env('FIREBASE_WEB_API_KEY'),
            'app_id' => env('FIREBASE_WEB_APP_ID'),
            'sender_id' => env('FIREBASE_SENDER_ID'),
            'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
            'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
        ],
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connexion Google
    |--------------------------------------------------------------------------
    |
    | Identifiants d'un « ID client OAuth » de type application web, créé dans
    | la console Google Cloud (API et services → Identifiants). L'URI de
    | redirection autorisée doit correspondre exactement à `redirect`, à la
    | barre oblique près.
    |
    | `mobile_client_ids` liste les identifiants clients de l'application
    | mobile — un par plateforme. Ils ne servent qu'à vérifier à qui un jeton
    | Google a été délivré : un jeton obtenu pour une autre application ne doit
    | jamais ouvrir un compte chez nous.
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/retour'),
        'mobile_client_ids' => array_values(array_filter(
            explode(',', (string) env('GOOGLE_MOBILE_CLIENT_IDS', '')),
        )),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
