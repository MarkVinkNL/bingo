<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoSubject extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function bingoCellValues(): HasMany
    {
        return $this->hasMany(BingoCellValue::class);
    }

    public function bingoCards(): HasMany
    {
        return $this->hasMany(BingoCard::class);
    }

    public function bingoBattles(): HasMany
    {
        return $this->hasMany(BingoBattle::class);
    }
}
