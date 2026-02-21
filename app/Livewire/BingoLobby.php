<?php

namespace App\Livewire;

use App\Models\BingoCard;
use App\Models\BingoSubject;
use App\Services\BingoCardGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BingoLobby extends Component
{
    /**
     * Active subjects with grid size label and optional existing card for the current user.
     *
     * @return Collection<int, object{subject: BingoSubject, gridSizeLabel: string, existingCard: BingoCard|null}>
     */
    public function getSubjectsWithCards(): Collection
    {
        $subjects = BingoSubject::query()
            ->where('is_active', true)
            ->withCount('bingoCellValues')
            ->orderBy('name')
            ->get();

        if ($subjects->isEmpty()) {
            return collect();
        }

        $generator = app(BingoCardGenerator::class);
        $userCards = BingoCard::query()
            ->where('user_id', Auth::id())
            ->whereIn('bingo_subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy('bingo_subject_id');

        return $subjects->map(function (BingoSubject $subject) use ($generator, $userCards) {
            $gridSize = $generator->resolveGridSize($subject->bingo_cell_values_count);
            $gridSizeLabel = $gridSize.'×'.$gridSize;

            return (object) [
                'subject' => $subject,
                'gridSizeLabel' => $gridSizeLabel,
                'existingCard' => $userCards->get($subject->id),
            ];
        });
    }

    /**
     * Current user's cards (one per subject), for "My cards" section.
     *
     * @return Collection<int, BingoCard>
     */
    public function getMyCards(): Collection
    {
        return BingoCard::query()
            ->where('user_id', Auth::id())
            ->with('bingoSubject')
            ->orderByDesc('generated_at')
            ->get();
    }

    public function render()
    {
        return view('livewire.bingo-lobby', [
            'subjectsWithCards' => $this->getSubjectsWithCards(),
            'myCards' => $this->getMyCards(),
        ])->layout('layouts.app', ['title' => __('Bingo')]);
    }
}
