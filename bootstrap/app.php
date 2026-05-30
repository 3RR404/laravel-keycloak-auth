<?php

use App\Http\Middleware\KeycloakAuthenticated;
use App\Http\Middleware\KeycloakRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Encrypt cookies (all cookies are encrypted by default in Laravel 11).
        $middleware->encryptCookies();

        // Named middleware aliases for use in route definitions.
        $middleware->alias([
            'keycloak.auth' => KeycloakAuthenticated::class,
            'keycloak.role' => KeycloakRole::class,
        ]);

        // Apply token-refresh check to every authenticated web request.
        $middleware->web(append: [
            // Add any global web middleware here.
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
