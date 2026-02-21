<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCardCell extends Model
{
    use HasFactory;
    protected $fillable = [
        'bingo_card_id',
        'bingo_cell_value_id',
        'position',
        'is_marked',
        'marked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_marked' => 'boolean',
            'marked_at' => 'datetime',
        ];
    }

    public function bingoCard(): BelongsTo
    {
        return $this->belongsTo(BingoCard::class);
    }

    public function bingoCellValue(): BelongsTo
    {
        return $this->belongsTo(BingoCellValue::class);
    }
}
