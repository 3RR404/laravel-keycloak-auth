<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes:
 *   ->middleware('keycloak.role:app-admin')
 *   ->middleware('keycloak.role:app-admin,app-moderator')  // any of these roles
 */
class KeycloakRole
{
    public function __construct(private readonly KeycloakService $keycloak) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user || ! $this->keycloak->hasAnyRole($user, $roles)) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Forbidden.'], 403)
                : abort(403, 'You do not have the required role to access this resource.');
        }

        return $next($request);
    }
}
