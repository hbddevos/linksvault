<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google API Configuration
    |--------------------------------------------------------------------------
    */

    // Clé API YouTube (pour les requêtes simples)
    'api_key' => env('YOUTUBE_API_KEY', ''),

    // OAuth 2 Client ID et Secret (pour les requêtes authentifiées)
    'client_id' => env('GOOGLE_CLIENT_ID', ''),
    'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI', ''),

    // Scopes nécessaires
    'scopes' => [
        'https://www.googleapis.com/auth/youtube.readonly',
        'https://www.googleapis.com/auth/youtube',
        // Google Drive (lecture seule)
        'https://www.googleapis.com/auth/drive.readonly',
        // Google Drive (lecture + modification)
        'https://www.googleapis.com/auth/drive',
        // Google Drive.metadata (plus léger)
        'https://www.googleapis.com/auth/drive.metadata.readonly',
    ],

    // URLs de l'API
    'api_url' => 'https://www.googleapis.com/youtube/v3',
];
