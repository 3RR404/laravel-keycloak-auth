<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Keycloak Server Configuration
    |--------------------------------------------------------------------------
    */
    // Browser-facing URL (used by Socialite to build the authorization redirect).
    'base_url' => env('KEYCLOAK_BASE_URL', 'http://localhost:8080'),

    // Container-to-container URL (used by PHP for token exchange / revoke calls).
    // Defaults to KEYCLOAK_BASE_URL so a non-Docker setup only needs one variable.
    'internal_url' => env('KEYCLOAK_INTERNAL_URL', env('KEYCLOAK_BASE_URL', 'http://keycloak:8080')),

    'realm' => env('KEYCLOAK_REALM', 'saas-app'),

    'client_id' => env('KEYCLOAK_CLIENT_ID', 'laravel-app'),

    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),

    'redirect_uri' => env('KEYCLOAK_REDIRECT_URI', 'http://localhost:8000/auth/callback'),

    /*
    |--------------------------------------------------------------------------
    | Token Settings
    |--------------------------------------------------------------------------
    | refresh_margin: refresh the access token this many seconds before it
    | actually expires, to prevent requests hitting an expired token.
    */
    'refresh_margin' => 60,

    /*
    |--------------------------------------------------------------------------
    | Role Mapping: Keycloak realm roles → Laravel gate abilities
    |--------------------------------------------------------------------------
    | Map Keycloak role names to arrays of Laravel ability strings.
    | Users receive every ability belonging to any of their Keycloak roles.
    */
    'role_map' => [
        'app-admin' => [
            'admin',
            'manage-users',
            'manage-content',
            'view-reports',
        ],
        'app-moderator' => [
            'manage-content',
            'view-reports',
        ],
        'app-user' => [
            'view-content',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Post-login / Post-logout Routes
    |--------------------------------------------------------------------------
    */
    'login_redirect'  => env('KEYCLOAK_LOGIN_REDIRECT', '/dashboard'),
    'logout_redirect' => env('KEYCLOAK_LOGOUT_REDIRECT', '/'),
];
