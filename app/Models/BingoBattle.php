<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class BingoBattle extends Model
{
    protected $table = 'bingo_battles';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bingo_subject_id',
        'created_by',
        'winner_id',
        'won_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'won_at' => 'datetime',
        ];
    }

    public function bingoSubject(): BelongsTo
    {
        return $this->belongsTo(BingoSubject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function isWon(): bool
    {
        return $this->winner_id !== null;
    }

    public function invites(): HasMany
    {
        return $this->hasMany(BingoBattleInvite::class);
    }

    /**
     * All participant cards: creator's card + accepted invitees' cards.
     *
     * @return Collection<int, BingoCard>
     */
    public function participantCards(): Collection
    {
        $creatorCard = BingoCard::query()
            ->where('battle_id', $this->id)
            ->where('user_id', $this->created_by)
            ->first();

        $inviteeCardIds = $this->invites()->accepted()->whereNotNull('bingo_card_id')->pluck('bingo_card_id');
        $inviteeCards = BingoCard::query()
            ->whereIn('id', $inviteeCardIds)
            ->get();

        $cards = collect();
        if ($creatorCard !== null) {
            $cards->push($creatorCard);
        }

        return $cards->merge($inviteeCards)->unique('id')->values();
    }
}
