<?php

namespace Database\Seeders;

use App\Models\BingoCellValue;
use App\Models\BingoSubject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BingoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedBingoSubjects();
    }

    private function seedBingoSubjects(): void
    {
        $subjects = [
            ['name' => 'Movies', 'values' => ['Star Wars', 'The Matrix', 'Inception', 'Titanic', 'Avatar', 'Jurassic Park', 'The Godfather', 'Pulp Fiction', 'Forrest Gump', 'The Shawshank Redemption', 'Interstellar', 'Gladiator']],
            ['name' => 'Animals', 'values' => ['Lion', 'Elephant', 'Giraffe', 'Zebra', 'Penguin', 'Dolphin', 'Eagle', 'Wolf', 'Bear', 'Tiger', 'Kangaroo', 'Panda', 'Monkey', 'Crocodile', 'Owl', 'Fox']],
        ];

        foreach ($subjects as $index => $data) {
            $subject = BingoSubject::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'is_active' => true,
            ]);

            foreach ($data['values'] as $order => $value) {
                BingoCellValue::create([
                    'bingo_subject_id' => $subject->id,
                    'value' => $value,
                    'sort_order' => $order,
                ]);
            }
        }
    }
}
