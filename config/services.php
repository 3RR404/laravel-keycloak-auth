<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Keycloak (via SocialiteProviders)
    |--------------------------------------------------------------------------
    | base_url  — Keycloak server root (without /realms/...)
    | realms    — realm name
    |--------------------------------------------------------------------------
    */
    'keycloak' => [
        'client_id'     => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect'      => env('KEYCLOAK_REDIRECT_URI'),
        'base_url'      => env('KEYCLOAK_BASE_URL'),
        'realms'        => env('KEYCLOAK_REALM'),
    ],

];
