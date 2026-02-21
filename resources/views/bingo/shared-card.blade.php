@extends('layouts.shared')

@section('title', ($card->bingoSubject->name ?? __('Bingo Card')) . ' – ' . __('Shared Bingo Card'))

@section('content')
  <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <flux:heading size="xl" level="1" class="!text-6xl">{{ $card->bingoSubject->name ?? __('Bingo Card') }}
        </flux:heading>
        @if ($card->isCompleted())
          <div class="flex flex-col gap-0.5">
            <flux:badge color="green" size="lg">{{ __('Completed') }}</flux:badge>
            <flux:text variant="subtle" class="text-xs">
              {{ __('Completed on :date', ['date' => $card->completed_at->translatedFormat('d M Y, H:i')]) }}
            </flux:text>
          </div>
        @endif
        <flux:text variant="subtle" class="text-xs">{{ __('Shared view only') }}</flux:text>
      </div>
      <flux:link :href="route('home')" variant="outline">
        {{ __('Back to home') }}
      </flux:link>
    </div>

    @if (!empty($completedBingoLines))
      <flux:callout variant="success" icon="check-badge" :heading="__('Bingo!')"
        :text="__('Completed lines: :lines', ['lines' => implode(', ', $completedBingoLines)])" />
    @endif

    @if ($card->bingoCardCells->isNotEmpty())
      <div class="bingo-card">
        <div class="bingo-card__grid" style="grid-template-columns: repeat({{ $card->grid_size }}, minmax(0, 1fr));">
          @foreach ($card->bingoCardCells as $cell)
            <div
              class="bingo-card__cell {{ $cell->is_marked ? 'bingo-card__cell--marked' : 'bingo-card__cell--default' }}">
              <span>{{ $cell->bingoCellValue?->value ?? '—' }}</span>
              @if ($cell->is_marked)
                <span class="bingo-card__cell-check" aria-hidden="true">✓</span>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
@endsection
