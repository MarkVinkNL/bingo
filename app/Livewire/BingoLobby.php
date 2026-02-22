<?php

namespace App\Livewire;

use App\Models\BingoCard;
use App\Models\BingoCellValueSuggestion;
use App\Models\BingoCellValueSuggestionValue;
use App\Models\BingoSubjectSuggestion;
use App\Models\BingoSubjectSuggestionValue;
use App\Models\BingoSubject;
use App\Services\BingoCardGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class BingoLobby extends Component
{
    use AuthorizesRequests;

    public bool $showSuggestionModal = false;

    public string $suggestedSubjectName = '';

    /** @var string Cell values, one per line */
    public string $suggestedCellValues = '';

    public bool $showCellValuesSuggestionModal = false;

    public ?int $cellValuesSuggestionSubjectId = null;

    /** @var string Cell values for existing subject, one per line */
    public string $suggestedCellValuesForSubject = '';

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
            ->whereNull('completed_at')
            ->latest('generated_at')
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

    /**
     * Remove a bingo card from the lobby (owner only). List re-renders after delete.
     */
    public function removeCard(string $uuid): void
    {
        $card = BingoCard::query()->where('uuid', $uuid)->first();

        if ($card === null) {
            return;
        }

        $this->authorize('delete', $card);
        $card->delete();
    }

    public function openSuggestionModal(): void
    {
        $this->showSuggestionModal = true;
        $this->resetSuggestionForm();
    }

    public function closeSuggestionModal(): void
    {
        $this->showSuggestionModal = false;
        $this->resetSuggestionForm();
    }

    protected function resetSuggestionForm(): void
    {
        $this->suggestedSubjectName = '';
        $this->suggestedCellValues = '';
        $this->resetValidation();
    }

    public function submitSuggestion(): void
    {
        $this->validate([
            'suggestedSubjectName' => ['required', 'string', 'max:255'],
            'suggestedCellValues' => ['required', 'string'],
        ], [], [
            'suggestedSubjectName' => __('Subject name'),
            'suggestedCellValues' => __('Cell values'),
        ]);

        $lines = array_values(array_filter(array_map('trim', explode("\n", $this->suggestedCellValues))));
        if (count($lines) < 1) {
            $this->addError('suggestedCellValues', __('Add at least one cell value (one per line).'));
            return;
        }

        $suggestion = BingoSubjectSuggestion::create([
            'suggested_name' => $this->suggestedSubjectName,
            'suggested_slug' => Str::slug($this->suggestedSubjectName),
            'status' => BingoSubjectSuggestion::STATUS_PENDING,
            'user_id' => Auth::id(),
        ]);

        foreach ($lines as $index => $line) {
            if ($line === '') {
                continue;
            }
            BingoSubjectSuggestionValue::create([
                'bingo_subject_suggestion_id' => $suggestion->id,
                'value' => $line,
                'sort_order' => $index,
            ]);
        }

        $this->closeSuggestionModal();
        $this->dispatch('suggestion-submitted');
    }

    public function openCellValuesSuggestionModal(int $subjectId): void
    {
        $subject = BingoSubject::query()
            ->where('id', $subjectId)
            ->where('is_active', true)
            ->first();

        if ($subject === null) {
            return;
        }

        $this->cellValuesSuggestionSubjectId = $subjectId;
        $this->suggestedCellValuesForSubject = '';
        $this->showCellValuesSuggestionModal = true;
        $this->resetValidation();
    }

    public function closeCellValuesSuggestionModal(): void
    {
        $this->showCellValuesSuggestionModal = false;
        $this->cellValuesSuggestionSubjectId = null;
        $this->suggestedCellValuesForSubject = '';
        $this->resetValidation();
    }

    public function submitCellValuesSuggestion(): void
    {
        $this->validate([
            'suggestedCellValuesForSubject' => ['required', 'string'],
        ], [], [
            'suggestedCellValuesForSubject' => __('Cell values'),
        ]);

        $subject = BingoSubject::query()
            ->where('id', $this->cellValuesSuggestionSubjectId)
            ->where('is_active', true)
            ->first();

        if ($subject === null) {
            $this->closeCellValuesSuggestionModal();
            return;
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $this->suggestedCellValuesForSubject))));
        if (count($lines) < 1) {
            $this->addError('suggestedCellValuesForSubject', __('Add at least one cell value (one per line).'));
            return;
        }

        $suggestion = BingoCellValueSuggestion::create([
            'bingo_subject_id' => $subject->id,
            'status' => BingoCellValueSuggestion::STATUS_PENDING,
            'user_id' => Auth::id(),
        ]);

        foreach ($lines as $index => $line) {
            if ($line === '') {
                continue;
            }
            BingoCellValueSuggestionValue::create([
                'bingo_cell_value_suggestion_id' => $suggestion->id,
                'value' => $line,
                'sort_order' => $index,
            ]);
        }

        $this->closeCellValuesSuggestionModal();
        $this->dispatch('cell-values-suggestion-submitted');
    }

    public function getCellValuesSuggestionSubject(): ?BingoSubject
    {
        if ($this->cellValuesSuggestionSubjectId === null) {
            return null;
        }

        return BingoSubject::query()
            ->where('id', $this->cellValuesSuggestionSubjectId)
            ->where('is_active', true)
            ->first();
    }

    public function render()
    {
        return view('livewire.bingo-lobby', [
            'subjectsWithCards' => $this->getSubjectsWithCards(),
            'myCards' => $this->getMyCards(),
            'cellValuesSuggestionSubject' => $this->getCellValuesSuggestionSubject(),
        ])->layout('layouts.app', ['title' => __('Bingo')]);
    }
}
