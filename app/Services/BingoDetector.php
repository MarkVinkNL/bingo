<?php

namespace App\Services;

use App\Models\BingoCard;

class BingoDetector
{
    /**
     * Get all completed lines (rows, columns, diagonals) for the card.
     * Card must have bingoCardCells loaded (with is_marked).
     *
     * @return array{rows: array<int>, columns: array<int>, diagonalMain: bool, diagonalAnti: bool}
     */
    public function getCompletedLines(BingoCard $card): array
    {
        $cells = $card->bingoCardCells->keyBy('position');
        $size = $card->grid_size;
        $total = $size * $size;

        $marked = [];
        for ($i = 0; $i < $total; $i++) {
            $marked[$i] = ($cells->get($i)?->is_marked ?? false);
        }

        $rows = $this->completedRows($size, $marked);
        $columns = $this->completedColumns($size, $marked);
        $diagonalMain = $this->isMainDiagonalComplete($size, $marked);
        $diagonalAnti = $this->isAntiDiagonalComplete($size, $marked);

        return [
            'rows' => $rows,
            'columns' => $columns,
            'diagonalMain' => $diagonalMain,
            'diagonalAnti' => $diagonalAnti,
        ];
    }

    /**
     * Check if the card has at least one completed line (any row, column, or diagonal).
     */
    public function hasBingo(BingoCard $card): bool
    {
        $lines = $this->getCompletedLines($card);

        return ! empty($lines['rows'])
            || ! empty($lines['columns'])
            || $lines['diagonalMain']
            || $lines['diagonalAnti'];
    }

    /**
     * Get human-readable labels for completed lines (1-based for display).
     *
     * @return array<int, string>
     */
    public function getCompletedLineLabels(BingoCard $card): array
    {
        $lines = $this->getCompletedLines($card);
        $labels = [];

        foreach ($lines['rows'] as $r) {
            $labels[] = __('Row :n', ['n' => $r + 1]);
        }
        foreach ($lines['columns'] as $c) {
            $labels[] = __('Column :n', ['n' => $c + 1]);
        }
        if ($lines['diagonalMain']) {
            $labels[] = __('Diagonal (↘)');
        }
        if ($lines['diagonalAnti']) {
            $labels[] = __('Diagonal (↙)');
        }

        return $labels;
    }

    /**
     * @param  array<int, bool>  $marked  position => is_marked
     * @return array<int>  row indices (0-based) that are complete
     */
    private function completedRows(int $size, array $marked): array
    {
        $complete = [];
        for ($r = 0; $r < $size; $r++) {
            $all = true;
            for ($c = 0; $c < $size; $c++) {
                $pos = $r * $size + $c;
                if (! ($marked[$pos] ?? false)) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                $complete[] = $r;
            }
        }

        return $complete;
    }

    /**
     * @param  array<int, bool>  $marked  position => is_marked
     * @return array<int>  column indices (0-based) that are complete
     */
    private function completedColumns(int $size, array $marked): array
    {
        $complete = [];
        for ($c = 0; $c < $size; $c++) {
            $all = true;
            for ($r = 0; $r < $size; $r++) {
                $pos = $r * $size + $c;
                if (! ($marked[$pos] ?? false)) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                $complete[] = $c;
            }
        }

        return $complete;
    }

    /**
     * Main diagonal: top-left to bottom-right (position 0, N+1, 2N+2, ...).
     *
     * @param  array<int, bool>  $marked
     */
    private function isMainDiagonalComplete(int $size, array $marked): bool
    {
        for ($i = 0; $i < $size; $i++) {
            $pos = $i * ($size + 1);
            if (! ($marked[$pos] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Anti diagonal: top-right to bottom-left (position N-1, 2N-2, ...).
     *
     * @param  array<int, bool>  $marked
     */
    private function isAntiDiagonalComplete(int $size, array $marked): bool
    {
        for ($i = 0; $i < $size; $i++) {
            $pos = $i * $size + ($size - 1 - $i);
            if (! ($marked[$pos] ?? false)) {
                return false;
            }
        }

        return true;
    }
}
