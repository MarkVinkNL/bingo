<?php

namespace Database\Factories;

use App\Models\BingoCard;
use App\Models\BingoSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BingoCard>
 */
class BingoCardFactory extends Factory
{
    protected $model = BingoCard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bingo_subject_id' => BingoSubject::factory(),
            'user_id' => User::factory(),
            'grid_size' => fake()->numberBetween(3, 6),
            'generated_at' => now(),
        ];
    }
}
