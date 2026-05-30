<?php

namespace App\Policies;

use App\Models\User;

/**
 * Example policy demonstrating Keycloak role → Laravel policy integration.
 * Register in AppServiceProvider::boot() via Gate::policy() if needed,
 * or rely on the auto-discovery (model name matching policy name).
 */
class ContentPolicy
{
    /** Admins bypass all checks via Gate::before in AppServiceProvider. */

    public function view(User $user): bool
    {
        return $user->hasAnyRole(['app-admin', 'app-moderator', 'app-user']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['app-admin', 'app-moderator']);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole(['app-admin', 'app-moderator']);
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
