<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'avatar',
        'locale',
        'keycloak_id',
        'keycloak_roles',
        'access_token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'token_expires_at'  => 'datetime',
            'keycloak_roles'    => 'array',
        ];
    }

    // -----------------------------------------------------------------------
    // Role helpers (convenience wrappers around KeycloakService)
    // -----------------------------------------------------------------------

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->keycloak_roles ?? [], true);
    }

    public function hasAnyRole(array $roles): bool
    {
        return ! empty(array_intersect($roles, $this->keycloak_roles ?? []));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('app-admin');
    }

    public function isModerator(): bool
    {
        return $this->hasAnyRole(['app-admin', 'app-moderator']);
    }
}
