<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoSubjectSuggestion extends Model
{
    protected $fillable = [
        'suggested_name',
        'suggested_slug',
        'status',
        'user_id',
        'approved_bingo_subject_id',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suggestionValues(): HasMany
    {
        return $this->hasMany(BingoSubjectSuggestionValue::class, 'bingo_subject_suggestion_id');
    }

    public function approvedBingoSubject(): BelongsTo
    {
        return $this->belongsTo(BingoSubject::class, 'approved_bingo_subject_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
