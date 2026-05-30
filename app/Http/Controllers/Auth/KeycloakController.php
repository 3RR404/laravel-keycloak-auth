<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\KeycloakService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class KeycloakController extends Controller
{
    public function __construct(private readonly KeycloakService $keycloak) {}

    /** Redirect the browser to the Keycloak login page. */
    public function redirect(): RedirectResponse
    {
        return $this->keycloak->redirect();
    }

    /** Handle the OIDC callback from Keycloak. */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning('Keycloak auth error', [
                'error'             => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ]);

            return redirect('/')->withErrors(['auth' => 'Authentication failed. Please try again.']);
        }

        try {
            $user = $this->keycloak->handleCallback($request);

            Auth::login($user, remember: true);

            $request->session()->regenerate();

            return redirect()->intended(config('keycloak.login_redirect', '/dashboard'));

        } catch (Throwable $e) {
            Log::error('Keycloak callback exception', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect('/')->withErrors(['auth' => 'Login failed. Please try again.']);
        }
    }

    /** Log out from both Laravel and Keycloak. */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            $postLogoutUrl = url(config('keycloak.logout_redirect', '/'));
            $keycloakLogoutUrl = $this->keycloak->buildLogoutUrl($user, $postLogoutUrl);

            return redirect($keycloakLogoutUrl);
        }

        return redirect(config('keycloak.logout_redirect', '/'));
    }
}
