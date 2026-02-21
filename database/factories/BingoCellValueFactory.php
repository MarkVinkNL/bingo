<?php

namespace Database\Factories;

use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BingoCellValue>
 */
class BingoCellValueFactory extends Factory
{
    protected $model = BingoCellValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bingo_subject_id' => BingoSubject::factory(),
            'value' => fake()->unique()->word(),
            'sort_order' => null,
        ];
    }
}
