<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoSubjectSuggestionValue extends Model
{
    protected $fillable = [
        'bingo_subject_suggestion_id',
        'value',
        'sort_order',
    ];

    public function bingoSubjectSuggestion(): BelongsTo
    {
        return $this->belongsTo(BingoSubjectSuggestion::class);
    }
}
