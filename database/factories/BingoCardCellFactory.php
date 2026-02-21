<?php

namespace Database\Factories;

use App\Models\BingoCard;
use App\Models\BingoCardCell;
use App\Models\BingoCellValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BingoCardCell>
 */
class BingoCardCellFactory extends Factory
{
    protected $model = BingoCardCell::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bingo_card_id' => BingoCard::factory(),
            'bingo_cell_value_id' => BingoCellValue::factory(),
            'position' => fake()->numberBetween(0, 35),
            'is_marked' => false,
            'marked_at' => null,
        ];
    }
}
