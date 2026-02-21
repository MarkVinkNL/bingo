<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

  @if ($myCards->isNotEmpty())
    <section>
      <flux:heading size="xl" level="2" class="mb-3">{{ __('My cards') }}</flux:heading>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($myCards as $card)
          <a
            href="{{ route('bingo.card', $card->uuid) }}"
            wire:navigate
            wire:key="card-{{ $card->uuid }}"
            class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 dark:hover:border-neutral-600 dark:hover:bg-neutral-800/50 cursor-pointer"
          >
            <div class="flex items-start justify-between gap-2">
              <flux:heading size="lg" level="3" class="min-w-0 flex-1">{{ $card->bingoSubject->name }}</flux:heading>
              @if ($card->isCompleted())
                <flux:badge color="green" size="sm">{{ __('Completed') }}</flux:badge>
              @endif
            </div>
            <flux:text variant="subtle">
              {{ __('Grid') }}: {{ $card->grid_size }}×{{ $card->grid_size }}
            </flux:text>
            @if ($card->isCompleted() && $card->completed_at)
              <flux:text variant="subtle" class="text-xs">
                {{ __('Completed on :date', ['date' => $card->completed_at->translatedFormat('d M Y')]) }}
              </flux:text>
            @endif
          </a>
        @endforeach
      </div>
    </section>
  @endif

  <section>
    <flux:heading size="xl" level="2" class="mb-3">{{ __('Subjects') }}</flux:heading>

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
          <a
            href="{{ $href }}"
            wire:navigate
            wire:key="subject-{{ $item->subject->id }}"
            class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 dark:hover:border-neutral-600 dark:hover:bg-neutral-800/50 cursor-pointer"
          >
            <flux:heading size="lg" level="3">{{ $item->subject->name }}</flux:heading>
            <flux:text variant="subtle">
              {{ __('Grid') }}: {{ $item->gridSizeLabel }}
            </flux:text>
          </a>
        @endforeach
      </div>
    @endif
  </section>
</div>
