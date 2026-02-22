<?php

namespace App\Policies;

use App\Models\BingoCard;
use App\Models\User;

class BingoCardPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model (owner or battle participant).
     */
    public function view(User $user, BingoCard $bingoCard): bool
    {
        if ($bingoCard->user_id !== null && $bingoCard->user_id === $user->id) {
            return true;
        }

        if ($bingoCard->battle_id === null) {
            return false;
        }

        $battle = $bingoCard->battle;
        if ($battle === null) {
            return false;
        }

        if ($battle->created_by === $user->id) {
            return true;
        }

        return $battle->invites()->where('user_id', $user->id)->accepted()->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model (owner only; e.g. toggle cell).
     */
    public function update(User $user, BingoCard $bingoCard): bool
    {
        return $bingoCard->user_id !== null && $bingoCard->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model (owner only).
     */
    public function delete(User $user, BingoCard $bingoCard): bool
    {
        return $bingoCard->user_id !== null && $bingoCard->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BingoCard $bingoCard): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BingoCard $bingoCard): bool
    {
        return false;
    }
}
