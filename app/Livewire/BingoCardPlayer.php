<?php

namespace App\Livewire;

use App\Models\BingoBattle;
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

    public bool $showShareModal = false;

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
     * No-op when the card is completed (view-only).
     */
    public function toggleCell(int $position): void
    {
        $card = BingoCard::query()
            ->where('uuid', $this->uuid)
            ->first();

        if ($card === null) {
            abort(404);
        }

        if ($card->isCompleted()) {
            return;
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
            $this->card->update(['completed_at' => now()]);
            $this->card->refresh();
            $this->js($this->bingoConfettiScript());

            if ($this->card->battle_id !== null) {
                $battle = BingoBattle::query()->find($this->card->battle_id);
                if ($battle !== null && $battle->winner_id === null) {
                    $battle->update([
                        'winner_id' => $this->card->user_id,
                        'won_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * JavaScript to run the completion confetti (3 bursts). Used by $this->js() so it runs
     * in the same response cycle and works regardless of wire:navigate or listener setup.
     */
    private function bingoConfettiScript(): string
    {
        return <<<'JS'
        (function(){
          if (typeof window.confetti !== 'function') return;
          var delay = 280;
          for (var i = 0; i < 3; i++) {
            (function(n){
              var x = 0.25 + Math.random() * 0.5;
              var y = 0.25 + Math.random() * 0.5;
              setTimeout(function(){
                window.confetti({ particleCount: 400, spread: 360, origin: { x: x, y: y }, disableForReducedMotion: true });
              }, n * delay);
            })(i);
          }
        })();
        JS;
    }

    /**
     * Regenerate the share token (invalidates the previous share link).
     * No-op when the card is completed (view-only).
     */
    public function regenerateShareToken(): void
    {
        if ($this->card === null || $this->card->isCompleted()) {
            return;
        }

        $this->authorize('update', $this->card);
        $this->card->regenerateShareToken();
        $this->card->refresh();
    }

    /**
     * Remove (delete) this bingo card and redirect to the lobby.
     * Works for both active and completed cards; owner only.
     */
    public function removeCard(): void
    {
        if ($this->card === null) {
            return;
        }

        $this->authorize('delete', $this->card);
        $this->card->delete();
        $this->redirect(route('bingo.index'), navigate: true);
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

    /**
     * Other participants' cards for battle sidebar (simplified: grid_size + position => is_marked).
     * Only when this card is part of a battle; excludes current user's card.
     *
     * @return array<int, array{userName: string, gridSize: int, cells: array<int, bool>, isWinner: bool}>
     */
    public function getOtherParticipantCards(): array
    {
        if ($this->card === null || $this->card->battle_id === null) {
            return [];
        }

        $battle = BingoBattle::query()->with('winner')->find($this->card->battle_id);
        if ($battle === null) {
            return [];
        }

        $currentUserId = (int) auth()->id();
        $winnerId = $battle->winner_id;

        $creatorCard = BingoCard::query()
            ->where('battle_id', $battle->id)
            ->where('user_id', $battle->created_by)
            ->with(['bingoCardCells', 'user'])
            ->first();

        $inviteeCardIds = $battle->invites()->accepted()->whereNotNull('bingo_card_id')->pluck('bingo_card_id');
        $inviteeCards = BingoCard::query()
            ->whereIn('id', $inviteeCardIds)
            ->with(['bingoCardCells', 'user'])
            ->get();

        $result = [];
        if ($creatorCard !== null && $creatorCard->user_id !== $currentUserId) {
            $result[] = [
                'userName' => $creatorCard->user?->name ?? __('Creator'),
                'gridSize' => $creatorCard->grid_size,
                'cells' => $creatorCard->bingoCardCells->keyBy('position')->map(fn ($c) => $c->is_marked)->all(),
                'isWinner' => $winnerId !== null && $creatorCard->user_id === $winnerId,
            ];
        }
        foreach ($inviteeCards as $card) {
            if ($card->user_id === $currentUserId) {
                continue;
            }
            $result[] = [
                'userName' => $card->user?->name ?? '',
                'gridSize' => $card->grid_size,
                'cells' => $card->bingoCardCells->keyBy('position')->map(fn ($c) => $c->is_marked)->all(),
                'isWinner' => $winnerId !== null && $card->user_id === $winnerId,
            ];
        }

        return $result;
    }

    /**
     * Battle winner (when this card is in a battle that has been won). Null if not a battle or no winner yet.
     */
    public function getBattleWinner(): ?\App\Models\User
    {
        if ($this->card === null || $this->card->battle_id === null) {
            return null;
        }

        $battle = BingoBattle::query()->with('winner')->find($this->card->battle_id);

        return $battle?->winner;
    }

    public function render()
    {
        return view('livewire.bingo-card-player', [
            'completedBingoLines' => $this->getCompletedBingoLines(),
            'shareUrl' => $this->getShareUrl(),
            'otherParticipantCards' => $this->getOtherParticipantCards(),
            'battleWinner' => $this->getBattleWinner(),
        ])->layout('layouts.app', ['title' => $this->card?->bingoSubject?->name ?? __('Bingo Card')]);
    }
}
