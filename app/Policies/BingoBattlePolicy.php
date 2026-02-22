<?php

namespace App\Policies;

use App\Models\BingoBattle;
use App\Models\User;

class BingoBattlePolicy
{
    /**
     * Determine whether the user can view the battle (creator or any invited user).
     */
    public function view(User $user, BingoBattle $bingoBattle): bool
    {
        if ($bingoBattle->created_by === $user->id) {
            return true;
        }

        return $bingoBattle->invites()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create battles.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
