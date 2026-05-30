<?php

use App\Http\Controllers\Auth\KeycloakController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Public routes
// -----------------------------------------------------------------------

Route::get('/', fn () => view('welcome'))->name('home');

// -----------------------------------------------------------------------
// Keycloak OIDC auth flow
// -----------------------------------------------------------------------

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('/redirect', [KeycloakController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [KeycloakController::class, 'callback'])->name('callback');
    Route::post('/logout', [KeycloakController::class, 'logout'])->name('logout');
});

// -----------------------------------------------------------------------
// Authenticated routes — requires valid Keycloak session + token refresh
// -----------------------------------------------------------------------

Route::middleware('keycloak.auth')->group(function (): void {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Moderator and admin area
    Route::middleware('keycloak.role:app-moderator,app-admin')
        ->prefix('manage')
        ->name('manage.')
        ->group(function (): void {
            Route::get('/content', fn () => view('manage.content'))->name('content');
        });

    // Admin-only area
    Route::middleware('keycloak.role:app-admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/', [DashboardController::class, 'admin'])->name('dashboard');
        });
});
