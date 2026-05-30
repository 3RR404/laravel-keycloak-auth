<?php

namespace App\Providers;

use App\Models\User;
use App\Services\KeycloakService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeycloakService::class);
    }

    public function boot(): void
    {
        $this->registerSocialiteDriver();
        $this->registerGateAbilities();
    }

    private function registerSocialiteDriver(): void
    {
        // Register the Keycloak Socialite provider via its event listener.
        $this->app['events']->listen(
            SocialiteWasCalled::class,
            \SocialiteProviders\Keycloak\KeycloakExtendSocialite::class
        );
    }

    private function registerGateAbilities(): void
    {
        /** @var KeycloakService $keycloak */
        $keycloak = $this->app->make(KeycloakService::class);

        // Derive every possible ability from the role map so they can be
        // checked with Gate::allows() / @can in Blade.
        $allAbilities = array_unique(
            array_merge(...array_values(config('keycloak.role_map', [])))
        );

        foreach ($allAbilities as $ability) {
            Gate::define($ability, function (User $user) use ($ability, $keycloak) {
                return in_array($ability, $keycloak->resolveAbilities($user), true);
            });
        }

        // Super-ability: admins bypass all other checks.
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });
    }
}
