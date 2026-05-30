<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class KeycloakAuthenticated
{
    public function __construct(private readonly KeycloakService $keycloak) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('auth.redirect');
        }

        $user = Auth::user();

        // Silently refresh the access token when it is about to expire.
        if ($this->keycloak->tokenNeedsRefresh($user)) {
            $refreshed = $this->keycloak->refreshTokens($user);

            if (! $refreshed) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson()
                    ? response()->json(['message' => 'Session expired.'], 401)
                    : redirect()->route('auth.redirect')->withErrors([
                        'auth' => 'Your session has expired. Please log in again.',
                    ]);
            }
        }

        return $next($request);
    }
}
