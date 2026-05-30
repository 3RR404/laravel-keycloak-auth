<?php

namespace App\Services;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class KeycloakService
{
    // Server-to-server calls (token exchange, refresh, revoke, userinfo).
    private string $internalUrl;

    // Browser-facing calls (authorization redirect, end-session redirect).
    private string $browserUrl;

    private string $realm;
    private string $clientId;
    private string $clientSecret;
    private Client $http;

    public function __construct()
    {
        $this->internalUrl   = rtrim(config('keycloak.internal_url'), '/');
        $this->browserUrl    = rtrim(config('keycloak.base_url'), '/');
        $this->realm         = config('keycloak.realm');
        $this->clientId      = config('keycloak.client_id');
        $this->clientSecret  = config('keycloak.client_secret');
        $this->http          = new Client(['timeout' => 10]);
    }

    // -----------------------------------------------------------------------
    // Auth flow
    // -----------------------------------------------------------------------

    /**
     * Redirect the browser to Keycloak login.
     * Socialite only builds the authorization URL here — no server-side HTTP call.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('keycloak')
            ->with(['kc_locale' => app()->getLocale()])
            ->redirect();
    }

    /**
     * Handle the OIDC callback.
     * We exchange the code ourselves via Guzzle (internal URL) so the PHP
     * container never tries to reach localhost:8080.
     * User identity is read from the ID token JWT — no extra userinfo call needed.
     */
    public function handleCallback(Request $request): User
    {
        $code = $request->get('code');

        if (! $code) {
            throw new RuntimeException('No authorization code in callback.');
        }

        // Exchange authorization code → tokens (server-to-server, internal URL).
        $tokens = $this->exchangeCode($code);

        // Decode the ID token for user identity — Keycloak includes it in all OIDC flows.
        // Falls back to the access token payload which also carries the user claims.
        $idToken  = $tokens['id_token'] ?? $tokens['access_token'];
        $userInfo = $this->decodeJwtPayload($idToken);

        if (empty($userInfo['sub'])) {
            throw new RuntimeException('Could not extract user identity from Keycloak token.');
        }

        $roles = $this->extractRoles($tokens['access_token'] ?? '');

        $user = User::updateOrCreate(
            ['keycloak_id' => $userInfo['sub']],
            [
                'name'              => $userInfo['name'] ?? $userInfo['preferred_username'] ?? $userInfo['email'],
                'email'             => $userInfo['email'],
                'email_verified_at' => ($userInfo['email_verified'] ?? false) ? now() : null,
                'avatar'            => $userInfo['picture'] ?? null,
                'locale'            => $userInfo['locale'] ?? null,
                'keycloak_roles'    => $roles,
                'access_token'      => Crypt::encryptString($tokens['access_token']),
                'refresh_token'     => isset($tokens['refresh_token'])
                    ? Crypt::encryptString($tokens['refresh_token'])
                    : null,
                'token_expires_at'  => now()->addSeconds((int) ($tokens['expires_in'] ?? 300)),
            ]
        );

        return $user;
    }

    private function exchangeCode(string $code): array
    {
        $response = $this->http->post($this->endpoint('token'), [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
                'redirect_uri'  => config('keycloak.redirect_uri'),
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        if (isset($data['error'])) {
            throw new RuntimeException(
                "Keycloak token exchange failed: {$data['error']} — {$data['error_description']}"
            );
        }

        return $data;
    }

    // -----------------------------------------------------------------------
    // Token refresh
    // -----------------------------------------------------------------------

    /**
     * Refresh the user's access token. Returns true on success, false when
     * the refresh token is expired (caller should force logout).
     */
    public function refreshTokens(User $user): bool
    {
        if (! $user->refresh_token) {
            return false;
        }

        try {
            $refresh = Crypt::decryptString($user->refresh_token);

            $response = $this->http->post($this->endpoint('token'), [
                'form_params' => [
                    'grant_type'    => 'refresh_token',
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refresh,
                ],
            ]);

            $data  = json_decode((string) $response->getBody(), true);
            $roles = $this->extractRoles($data['access_token'] ?? '');

            $user->update([
                'access_token'     => Crypt::encryptString($data['access_token']),
                'refresh_token'    => isset($data['refresh_token'])
                    ? Crypt::encryptString($data['refresh_token'])
                    : $user->refresh_token,
                'token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 300)),
                'keycloak_roles'   => $roles,
            ]);

            return true;

        } catch (RequestException $e) {
            Log::warning('Keycloak token refresh failed', [
                'user_id' => $user->id,
                'status'  => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
            ]);

            return false;
        }
    }

    public function tokenNeedsRefresh(User $user): bool
    {
        if (! $user->token_expires_at) {
            return true;
        }

        return $user->token_expires_at->subSeconds(config('keycloak.refresh_margin', 60))->isPast();
    }

    // -----------------------------------------------------------------------
    // Logout
    // -----------------------------------------------------------------------

    /**
     * Revoke the refresh token (server-to-server), then return the Keycloak
     * end-session URL for the browser to follow (uses browserUrl so the
     * user's browser can actually reach it).
     */
    public function buildLogoutUrl(User $user, string $postLogoutRedirect): string
    {
        $this->revokeRefreshToken($user);

        $params = http_build_query([
            'client_id'                => $this->clientId,
            'post_logout_redirect_uri' => $postLogoutRedirect,
        ]);

        return "{$this->browserUrl}/realms/{$this->realm}/protocol/openid-connect/logout?{$params}";
    }

    private function revokeRefreshToken(User $user): void
    {
        if (! $user->refresh_token) {
            return;
        }

        try {
            $refresh = Crypt::decryptString($user->refresh_token);

            $this->http->post($this->endpoint('revoke'), [
                'form_params' => [
                    'client_id'       => $this->clientId,
                    'client_secret'   => $this->clientSecret,
                    'token'           => $refresh,
                    'token_type_hint' => 'refresh_token',
                ],
            ]);
        } catch (RequestException $e) {
            Log::warning('Keycloak refresh token revocation failed', ['user_id' => $user->id]);
        }
    }

    // -----------------------------------------------------------------------
    // Roles
    // -----------------------------------------------------------------------

    private function decodeJwtPayload(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return [];
        }

        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true) ?? [];
    }

    /**
     * Extract app-specific realm roles from the access token JWT payload.
     * No signature verification needed — Keycloak validated the token during exchange.
     */
    public function extractRoles(string $accessToken): array
    {
        if (empty($accessToken)) {
            return [];
        }

        $realmRoles = $this->decodeJwtPayload($accessToken)['realm_access']['roles'] ?? [];

        // Keep only app-specific roles; strip Keycloak internals.
        return array_values(array_filter(
            $realmRoles,
            fn (string $role) => str_starts_with($role, 'app-')
        ));
    }

    public function resolveAbilities(User $user): array
    {
        $map       = config('keycloak.role_map', []);
        $abilities = [];

        foreach ($user->keycloak_roles ?? [] as $role) {
            $abilities = array_merge($abilities, $map[$role] ?? []);
        }

        return array_unique($abilities);
    }

    public function hasRole(User $user, string $role): bool
    {
        return in_array($role, $user->keycloak_roles ?? [], true);
    }

    public function hasAnyRole(User $user, array $roles): bool
    {
        return ! empty(array_intersect($roles, $user->keycloak_roles ?? []));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function endpoint(string $name): string
    {
        return "{$this->internalUrl}/realms/{$this->realm}/protocol/openid-connect/{$name}";
    }
}
