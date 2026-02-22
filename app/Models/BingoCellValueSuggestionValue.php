<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCellValueSuggestionValue extends Model
{
    protected $fillable = [
        'bingo_cell_value_suggestion_id',
        'value',
        'sort_order',
    ];

    public function bingoCellValueSuggestion(): BelongsTo
    {
        return $this->belongsTo(BingoCellValueSuggestion::class);
    }
}
