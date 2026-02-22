<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BingoCard extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'bingo_subject_id',
        'battle_id',
        'grid_size',
        'generated_at',
        'completed_at',
        'share_token',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    protected static function booted(): void
    {
        static::creating(function (BingoCard $card): void {
            if (empty($card->uuid)) {
                $card->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function bingoSubject(): BelongsTo
    {
        return $this->belongsTo(BingoSubject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function battle(): BelongsTo
    {
        return $this->belongsTo(BingoBattle::class, 'battle_id');
    }

    public function bingoCardCells(): HasMany
    {
        return $this->hasMany(BingoCardCell::class)->orderBy('position');
    }

    /**
     * Ensure the card has a share token (create one if missing). Returns the token.
     */
    public function ensureShareToken(): string
    {
        if (empty($this->share_token)) {
            $token = Str::random(48);
            $this->update(['share_token' => $token]);
            $this->share_token = $token;
        }

        return $this->share_token ?? '';
    }

    /**
     * Regenerate the share token (invalidates previous share link).
     */
    public function regenerateShareToken(): string
    {
        $token = Str::random(48);
        $this->update(['share_token' => $token]);
        $this->share_token = $token;

        return $token;
    }

    /**
     * Check if this card can be viewed with the given share token.
     */
    public function isValidShareToken(?string $token): bool
    {
        return $token !== null && $this->share_token !== null && hash_equals($this->share_token, $token);
    }
}
