<?php

namespace App\Policies;

use App\Models\BingoBattleInvite;
use App\Models\User;

class BingoBattleInvitePolicy
{
    /**
     * Determine whether the user can view the invite (the invited user only).
     */
    public function view(User $user, BingoBattleInvite $invite): bool
    {
        return $invite->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the invite (accept/decline - invited user only).
     */
    public function update(User $user, BingoBattleInvite $invite): bool
    {
        return $invite->user_id === $user->id;
    }
}
