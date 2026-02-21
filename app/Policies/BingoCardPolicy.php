<?php

namespace App\Policies;

use App\Models\BingoCard;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
     * Determine whether the user can view the model (owner only).
     */
    public function view(User $user, BingoCard $bingoCard): bool
    {
        return $bingoCard->user_id !== null && $bingoCard->user_id === $user->id;
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
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BingoCard $bingoCard): bool
    {
        return false;
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
