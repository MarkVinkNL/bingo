<?php

namespace App\Livewire;

use App\Models\BingoCard;
use App\Services\BingoDetector;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class BingoCardPlayer extends Component
{
    use AuthorizesRequests;

    /** @var string Card UUID from route (validated by route constraint) or passed for testing. */
    public string $uuid = '';

    public ?BingoCard $card = null;

    public function mount(): void
    {
        if (request()->route() && request()->route()->hasParameter('uuid')) {
            $this->uuid = (string) request()->route('uuid');
        }

        if ($this->uuid === '') {
            abort(404);
        }

        $this->card = BingoCard::query()
            ->where('uuid', $this->uuid)
            ->with(['bingoCardCells.bingoCellValue', 'bingoSubject'])
            ->first();

        if ($this->card === null) {
            abort(404);
        }

        $this->authorize('view', $this->card);
    }

    /**
     * Toggle the marked state of a cell by position (0 .. grid_size² - 1).
     */
    public function toggleCell(int $position): void
    {
        $card = BingoCard::query()
            ->where('uuid', $this->uuid)
            ->first();

        if ($card === null) {
            abort(404);
        }

        $this->authorize('update', $card);

        $maxPosition = $card->grid_size * $card->grid_size - 1;
        if ($position < 0 || $position > $maxPosition) {
            return;
        }

        $cell = $card->bingoCardCells()->where('position', $position)->first();
        if ($cell === null) {
            return;
        }

        $cell->update([
            'is_marked' => ! $cell->is_marked,
            'marked_at' => ! $cell->is_marked ? now() : null,
        ]);

        $this->card = $card->load(['bingoCardCells.bingoCellValue', 'bingoSubject']);

        if (app(BingoDetector::class)->hasBingo($this->card)) {
            $this->dispatch('bingo');
        }
    }

    /**
     * Regenerate the share token (invalidates the previous share link).
     */
    public function regenerateShareToken(): void
    {
        if ($this->card === null) {
            return;
        }

        $this->authorize('update', $this->card);
        $this->card->regenerateShareToken();
        $this->card->refresh();
    }

    /**
     * Get the share URL for this card (ensures a token exists).
     */
    public function getShareUrl(): ?string
    {
        if ($this->card === null) {
            return null;
        }

        $token = $this->card->ensureShareToken();

        return route('bingo.card.shared', [$this->card->uuid, $token]);
    }

    /**
     * Completed bingo lines (rows, columns, diagonals) for the current card.
     *
     * @return array<int, string>
     */
    public function getCompletedBingoLines(): array
    {
        if ($this->card === null || $this->card->bingoCardCells->isEmpty()) {
            return [];
        }

        return app(BingoDetector::class)->getCompletedLineLabels($this->card);
    }

    public function render()
    {
        return view('livewire.bingo-card-player', [
            'completedBingoLines' => $this->getCompletedBingoLines(),
            'shareUrl' => $this->getShareUrl(),
        ])->layout('layouts.app', ['title' => $this->card?->bingoSubject?->name ?? __('Bingo Card')]);
    }
}
