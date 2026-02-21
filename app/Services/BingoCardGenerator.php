<?php

namespace App\Services;

use App\Models\BingoCard;
use App\Models\BingoSubject;
use Illuminate\Support\Carbon;

class BingoCardGenerator
{
    /**
     * Resolve grid size (N for N×N) from cell value count.
     * Minimum 1 value: use 3×3 with replacement. Otherwise use floor(sqrt(count)) clamped to 3–6.
     *
     * @return int Grid size 3–6
     */
    public function resolveGridSize(int $cellCount): int
    {
        if ($cellCount < 9) {
            return 3;
        }

        return min(6, max(3, (int) floor(sqrt($cellCount))));
    }

    /**
     * Select cell value IDs for a card: without replacement if enough values, with replacement otherwise.
     *
     * @return array<int, int> List of bingo_cell_value IDs (length = $cellsNeeded)
     */
    public function selectCellValueIds(BingoSubject $subject, int $cellsNeeded): array
    {
        $cellCount = $subject->bingoCellValues()->count();

        if ($cellCount >= $cellsNeeded) {
            return $subject->bingoCellValues()
                ->inRandomOrder()
                ->limit($cellsNeeded)
                ->pluck('id')
                ->all();
        }

        $ids = $subject->bingoCellValues()->pluck('id')->all();
        $result = [];
        for ($i = 0; $i < $cellsNeeded; $i++) {
            $result[] = $ids[array_rand($ids)];
        }

        return $result;
    }

    /**
     * Generate a new card for the subject and user, or return existing card (one per subject per user).
     */
    public function generateOrGetCard(BingoSubject $subject, int $userId): BingoCard
    {
        $existing = BingoCard::where('user_id', $userId)
            ->where('bingo_subject_id', $subject->id)
            ->whereNull('completed_at')
            ->latest('generated_at')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $subject->loadCount('bingoCellValues');
        if ($subject->bingo_cell_values_count < 1) {
            throw new \InvalidArgumentException('Subject must have at least 1 cell value.');
        }

        $cellCount = $subject->bingo_cell_values_count;
        $gridSize = $this->resolveGridSize($cellCount);
        $cellsNeeded = $gridSize * $gridSize;
        $cellValueIds = $this->selectCellValueIds($subject, $cellsNeeded);

        $card = new BingoCard([
            'bingo_subject_id' => $subject->id,
            'grid_size' => $gridSize,
            'generated_at' => Carbon::now(),
        ]);
        $card->user_id = $userId;
        $card->save();

        foreach ($cellValueIds as $position => $bingoCellValueId) {
            $card->bingoCardCells()->create([
                'bingo_cell_value_id' => $bingoCellValueId,
                'position' => $position,
                'is_marked' => false,
            ]);
        }

        return $card->load('bingoCardCells');
    }
}
