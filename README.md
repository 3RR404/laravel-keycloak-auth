# Laravel + Keycloak Auth Starter Kit

Production-ready OIDC authentication for SaaS apps — Laravel 11, Keycloak 26, Docker Compose, PHP 8.3.

---

## Stack

| Layer        | Technology            |
|--------------|-----------------------|
| App          | Laravel 11 / PHP 8.3  |
| Auth server  | Keycloak 26           |
| Database     | PostgreSQL 16         |
| Web server   | Nginx 1.27            |
| Container    | Docker Compose        |

---

## Quick Start (5 minutes)

### 1. Clone & configure

```bash
git clone <this-repo> laravel-keycloak-auth
cd laravel-keycloak-auth
cp .env.example .env
```

> **Production:** Change `KEYCLOAK_CLIENT_SECRET` in both `.env` and `keycloak/realm-export.json` to a strong random value before deploying.

### 2. Start all services

```bash
docker compose up -d
```

First run downloads images and imports the realm (~2-3 min). Watch progress:

```bash
docker compose logs -f keycloak
# wait for: "Listening on: http://0.0.0.0:8080"
```

### 3. Install Laravel dependencies

```bash
docker compose exec app composer install
```

### 4. Generate app key & migrate

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

### 5. Open the app

| Service        | URL                                      |
|----------------|------------------------------------------|
| Laravel app    | http://localhost:8000                    |
| Keycloak admin | http://localhost:8080/admin (admin/admin) |

Click **Login with Keycloak** → you will be redirected to Keycloak → log in → redirected back and auto-registered as a Laravel user.

---

## Creating a Test User in Keycloak

1. Open http://localhost:8080/admin  
2. Select the **saas-app** realm  
3. Go to **Users → Add user**  
4. Fill in username + email, save  
5. Go to **Credentials** → set a password (toggle "Temporary" off)  
6. Go to **Role mapping → Assign role** → pick `app-admin`, `app-moderator`, or `app-user`

---

## Architecture

### Auth Flow

```
Browser
  │
  ├── GET /auth/redirect
  │     └── KeycloakController::redirect()
  │           └── Socialite redirects to Keycloak login page
  │
  ├── [user logs in at Keycloak]
  │
  ├── GET /auth/callback?code=...
  │     └── KeycloakController::callback()
  │           ├── Socialite exchanges code → access_token + refresh_token
  │           ├── Roles extracted from JWT access_token payload
  │           ├── User upserted in local DB (keycloak_id as unique key)
  │           └── Auth::login($user) → redirect to /dashboard
  │
  └── POST /auth/logout
        └── KeycloakController::logout()
              ├── Revoke refresh_token at Keycloak /revoke endpoint
              ├── Invalidate Laravel session
              └── Redirect to Keycloak end-session URL
```

### Token Refresh

Every authenticated request passes through `KeycloakAuthenticated` middleware. When the stored access token is within 60 seconds of expiry (configurable via `keycloak.refresh_margin`), the middleware silently calls Keycloak's `/token` endpoint with the refresh token. If the refresh token is also expired, the user is logged out and redirected to login.

### Role-Based Access

Keycloak **realm roles** are stored in the `keycloak_roles` JSON column on the `users` table after each login or token refresh. The role map in `config/keycloak.php` translates them into Laravel Gate abilities:

```php
// config/keycloak.php
'role_map' => [
    'app-admin'     => ['admin', 'manage-users', 'manage-content', 'view-reports'],
    'app-moderator' => ['manage-content', 'view-reports'],
    'app-user'      => ['view-content'],
],
```

Use anywhere in Laravel:

```php
// Blade
@can('manage-content') ... @endcan

// Controller / anywhere
Gate::allows('manage-users');

// Middleware on routes
Route::middleware('keycloak.role:app-admin')->group(...);
Route::middleware('keycloak.role:app-moderator,app-admin')->group(...); // any of these roles

// Model helpers
$user->hasRole('app-admin');
$user->isAdmin();
$user->isModerator();
```

---

## File Structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/Auth/KeycloakController.php   ← OIDC redirect/callback/logout
│   │   ├── Controllers/DashboardController.php
│   │   ├── Middleware/KeycloakAuthenticated.php       ← auth check + token refresh
│   │   └── Middleware/KeycloakRole.php                ← role gate middleware
│   ├── Models/User.php                                ← Keycloak fields + role helpers
│   ├── Policies/ContentPolicy.php                     ← example role-based policy
│   ├── Providers/AppServiceProvider.php               ← Gate wiring + Socialite setup
│   └── Services/KeycloakService.php                   ← all Keycloak HTTP calls
├── config/
│   ├── keycloak.php                                   ← role map, URLs, margins
│   └── services.php                                   ← Socialite Keycloak config
├── database/migrations/
│   └── 0001_01_01_000000_create_users_table.php       ← includes keycloak_* columns
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile + php.ini
├── keycloak/
│   └── realm-export.json                              ← auto-imported on first start
├── routes/web.php                                     ← auth routes + protected groups
└── routes/api.php
```

---

## Configuration Reference

All settings live in `.env` and `config/keycloak.php`.

| Variable                | Default                          | Description                        |
|-------------------------|----------------------------------|------------------------------------|
| `KEYCLOAK_BASE_URL`     | `http://keycloak:8080`           | Keycloak server root URL           |
| `KEYCLOAK_REALM`        | `saas-app`                       | Realm name                         |
| `KEYCLOAK_CLIENT_ID`    | `laravel-app`                    | OIDC client ID                     |
| `KEYCLOAK_CLIENT_SECRET`| *(set this)*                     | Client secret (confidential client)|
| `KEYCLOAK_REDIRECT_URI` | `http://localhost:8000/auth/callback` | Must match Keycloak client config |

### Token Refresh Margin

```php
// config/keycloak.php
'refresh_margin' => 60,  // refresh 60s before expiry
```

### Adding a New Role

1. Create the role in Keycloak Admin → `saas-app` realm → Roles  
2. Add it to `config/keycloak.php` under `role_map`  
3. The role prefix filter in `KeycloakService::extractRoles()` only passes through roles that start with `app-` — adjust the filter if you use a different naming convention

---

## Production Checklist

- [ ] Generate a strong `KEYCLOAK_CLIENT_SECRET` and update it in both `.env` and `keycloak/realm-export.json`
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Set `APP_URL` to your real domain
- [ ] Update `KEYCLOAK_BASE_URL` to your Keycloak server URL
- [ ] Update `KEYCLOAK_REDIRECT_URI` to your real callback URL
- [ ] Update `redirectUris` and `webOrigins` in Keycloak Admin for your production domain
- [ ] Enable SSL (`KC_HTTPS_*` env vars) and set `sslRequired: all` in the realm
- [ ] Enable OPcache: set `PHP_OPCACHE_ENABLE=1` in Docker or `php.ini`
- [ ] Set `SESSION_ENCRYPT=true` (already default in this kit)
- [ ] Run `php artisan config:cache && php artisan route:cache`
- [ ] Point `KC_DB_*` to a managed PostgreSQL instance (RDS, Cloud SQL, etc.)
- [ ] Set `KEYCLOAK_ADMIN_PASSWORD` to a strong value
- [ ] Disable `registrationAllowed` in Keycloak realm (already off in the export)

---

## Useful Commands

```bash
# Tail all logs
docker compose logs -f

# Run artisan commands
docker compose exec app php artisan migrate
docker compose exec app php artisan tinker

# Reset everything (WARNING: deletes all data)
docker compose down -v && docker compose up -d

# Rebuild the PHP container after Dockerfile changes
docker compose up -d --build app
```

---

## License

MIT
