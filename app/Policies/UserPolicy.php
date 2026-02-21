<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Admins and superadmins may list users in Filament.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->canAccessPanel() ?? false;
    }

    /**
     * Admins and superadmins may view a user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role?->canAccessPanel() ?? false;
    }

    /**
     * No creating users via Filament (users come from Socialite/Fortify).
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Admins and superadmins may edit users (role change is gated in the form).
     */
    public function update(User $user, User $model): bool
    {
        return $user->role?->canAccessPanel() ?? false;
    }

    /**
     * Only superadmins may delete users.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isSuperadmin();
    }
}
