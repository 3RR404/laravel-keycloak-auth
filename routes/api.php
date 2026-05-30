<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// All API routes require Keycloak authentication.
// Token refresh is handled automatically by the middleware.
// -----------------------------------------------------------------------

Route::middleware('keycloak.auth')->group(function (): void {

    Route::get('/user', function (Request $request) {
        return response()->json([
            'id'     => $request->user()->id,
            'name'   => $request->user()->name,
            'email'  => $request->user()->email,
            'roles'  => $request->user()->keycloak_roles,
            'avatar' => $request->user()->avatar,
        ]);
    });

    // Admin-only API endpoints
    Route::middleware('keycloak.role:app-admin')->prefix('admin')->group(function (): void {
        Route::get('/stats', function () {
            return response()->json(['message' => 'Admin stats endpoint']);
        });
    });
});
