<?php

namespace App\Models;

use App\Enums\BingoBattleInviteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoBattleInvite extends Model
{
    protected $table = 'bingo_battle_invites';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'bingo_battle_id',
        'user_id',
        'status',
        'bingo_card_id',
        'responded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BingoBattleInviteStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    public function bingoBattle(): BelongsTo
    {
        return $this->belongsTo(BingoBattle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bingoCard(): BelongsTo
    {
        return $this->belongsTo(BingoCard::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', BingoBattleInviteStatus::Pending);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', BingoBattleInviteStatus::Accepted);
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', BingoBattleInviteStatus::Declined);
    }
}
