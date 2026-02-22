<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

  @if ($myCards->isNotEmpty())
    <section>
      <flux:heading size="xl" level="2" class="mb-3">{{ __('My Bingo cards') }}</flux:heading>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($myCards as $card)
          <div wire:key="card-{{ $card->uuid }}"
            class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 dark:hover:border-neutral-600 dark:hover:bg-neutral-800/50">
            <a href="{{ route('bingo.card', $card->uuid) }}" wire:navigate
              class="flex items-start justify-between gap-2 cursor-pointer">
              <flux:heading size="lg" level="3" class="min-w-0 flex-1">{{ $card->bingoSubject->name }}
              </flux:heading>
              @if ($card->isCompleted())
                <flux:badge color="green" size="sm">{{ __('Completed') }}</flux:badge>
              @endif
            </a>

            <div class="flex items-center justify-between gap-2">
              <a href="{{ route('bingo.card', $card->uuid) }}" wire:navigate
                class="min-w-0 flex-1 flex flex-col gap-0.5 cursor-pointer">
                <flux:text variant="subtle">
                  {{ __('Grid') }}: {{ $card->grid_size }}&times;{{ $card->grid_size }}
                </flux:text>
                @if ($card->isCompleted() && $card->completed_at)
                  <flux:text variant="subtle" class="text-xs">
                    {{ __('Completed on :date', ['date' => $card->completed_at->translatedFormat('d M Y')]) }}
                  </flux:text>
                @endif
              </a>
              <flux:button variant="ghost" color="red" icon="trash" size="sm"
                aria-label="{{ __('Remove card') }}" wire:click="removeCard('{{ $card->uuid }}')"
                wire:confirm="{{ __('Remove this bingo card? This cannot be undone.') }}" />
            </div>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  <section class="mt-6">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
      <flux:heading size="xl" level="2" class="m-0">{{ __('Make a new bingo card') }}</flux:heading>
      <flux:button variant="outline" icon="light-bulb" wire:click="openSuggestionModal">
        {{ __('Suggest a subject') }}
      </flux:button>
    </div>

    @if ($subjectsWithCards->isEmpty())
      <flux:text variant="subtle">{{ __('No active subjects yet.') }}</flux:text>
    @else
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($subjectsWithCards as $item)
          @php
            $href = $item->existingCard
                ? route('bingo.card', $item->existingCard->uuid)
                : route('bingo.play', $item->subject->slug);
          @endphp
          <div wire:key="subject-{{ $item->subject->id }}"
            class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 dark:hover:border-neutral-600 dark:hover:bg-neutral-800/50">
            <a href="{{ $href }}" wire:navigate class="min-w-0 flex-1 cursor-pointer">
              <flux:heading size="lg" level="3">{{ $item->subject->name }}</flux:heading>
            </a>
            <div class="flex items-center justify-between gap-2">
              <a href="{{ $href }}" wire:navigate
                class="min-w-0 flex-1 cursor-pointer">
                <flux:text variant="subtle">
                  {{ __('Grid') }}: {{ $item->gridSizeLabel }}
                </flux:text>
              </a>
              <flux:button variant="ghost" icon="pencil-square" size="sm"
                aria-label="{{ __('Suggest new cell values') }}"
                wire:click="openCellValuesSuggestionModal({{ $item->subject->id }})" />
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </section>

  <flux:modal name="suggest-subject" wire:model="showSuggestionModal" class="sm:max-w-lg">
    <form wire:submit="submitSuggestion" class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Suggest a new subject') }}</flux:heading>
      <flux:text variant="subtle">{{ __('Propose a new bingo subject with cell values. An admin can review and approve it.') }}</flux:text>

      <flux:field>
        <flux:label for="suggested-subject-name">{{ __('Subject name') }}</flux:label>
        <flux:input id="suggested-subject-name" type="text" wire:model="suggestedSubjectName"
          placeholder="{{ __('e.g. Movies') }}" />
        <flux:error name="suggestedSubjectName" />
      </flux:field>

      <flux:field>
        <flux:label for="suggested-cell-values">{{ __('Cell values (one per line)') }}</flux:label>
        <flux:textarea id="suggested-cell-values" wire:model="suggestedCellValues" rows="8"
          placeholder="{{ __('e.g. Lion') }}&#10;{{ __('e.g. Elephant') }}" />
        <flux:error name="suggestedCellValues" />
      </flux:field>

      <div class="flex flex-wrap justify-end gap-2">
        <flux:button variant="ghost" type="button" wire:click="closeSuggestionModal">
          {{ __('Cancel') }}
        </flux:button>
        <flux:button type="submit" variant="primary">
          {{ __('Submit suggestion') }}
        </flux:button>
      </div>
    </form>
  </flux:modal>

  <flux:modal name="suggest-cell-values" wire:model="showCellValuesSuggestionModal" class="sm:max-w-lg">
    @if ($cellValuesSuggestionSubject)
      <form wire:submit="submitCellValuesSuggestion" class="flex flex-col gap-4">
        <flux:heading size="lg" level="2">{{ __('Suggest cell values') }}</flux:heading>
        <flux:text variant="subtle">
          {{ __('Add new cell value suggestions for :subject. An admin can review and approve them.', ['subject' => $cellValuesSuggestionSubject->name]) }}
        </flux:text>

        <flux:field>
          <flux:label for="suggested-cell-values-for-subject">{{ __('Cell values (one per line)') }}</flux:label>
          <flux:textarea id="suggested-cell-values-for-subject" wire:model="suggestedCellValuesForSubject" rows="8"
            placeholder="{{ __('e.g. Lion') }}&#10;{{ __('e.g. Elephant') }}" />
          <flux:error name="suggestedCellValuesForSubject" />
        </flux:field>

        <div class="flex flex-wrap justify-end gap-2">
          <flux:button variant="ghost" type="button" wire:click="closeCellValuesSuggestionModal">
            {{ __('Cancel') }}
          </flux:button>
          <flux:button type="submit" variant="primary">
            {{ __('Submit suggestion') }}
          </flux:button>
        </div>
      </form>
    @endif
  </flux:modal>
</div>
