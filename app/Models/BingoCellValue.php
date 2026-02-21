<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoCellValue extends Model
{
    use HasFactory;
    protected $fillable = [
        'bingo_subject_id',
        'value',
        'sort_order',
    ];

    public function bingoSubject(): BelongsTo
    {
        return $this->belongsTo(BingoSubject::class);
    }
}
