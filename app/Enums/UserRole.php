<?php

namespace App\Enums;

enum UserRole: string
{
    case Player = 'player';
    case Admin = 'admin';
    case Superadmin = 'superadmin';

    /**
     * Whether this role can access the Filament admin panel.
     */
    public function canAccessPanel(): bool
    {
        return $this === self::Admin || $this === self::Superadmin;
    }

    /**
     * Whether this role can assign or change other users' roles.
     */
    public function canManageRoles(): bool
    {
        return $this === self::Superadmin;
    }
}
