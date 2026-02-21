@php
    $card = $card ?? null;
    if (! $card) {
        return;
    }
    $cells = $card->bingoCardCells->sortBy('position')->values();
    $size = $card->grid_size;
@endphp
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
    <div class="p-6">
        {{-- Bingo card: square grid with 4px dividing lines --}}
        <div
            class="inline-grid w-full max-w-2xl border-4 border-gray-300 dark:border-gray-600"
            style="grid-template-columns: repeat({{ $size }}, minmax(0, 1fr)); grid-auto-rows: 1fr; aspect-ratio: 1 / 1;"
        >
            @foreach ($cells as $cell)
                @php
                    $isLastCol = ($cell->position % $size) === $size - 1;
                    $isLastRow = $cell->position >= $size * $size - $size;
                @endphp
                <div
                    class="cell flex min-h-0 items-center justify-center border-r-4 border-b-4 border-gray-300 p-2 text-center text-sm font-medium dark:border-gray-600 {{
                        $isLastCol ? 'border-r-0' : ''
                    }} {{
                        $isLastRow ? 'border-b-0' : ''
                    }} {{
                        $cell->is_marked
                            ? 'bg-green-100 text-green-800 dark:bg-green-500/25 dark:text-green-300'
                            : 'bg-white dark:bg-gray-800/50'
                    }}"
                >
                    <span class="line-clamp-2 break-words">{{ $cell->bingoCellValue?->value ?? '—' }}</span>
                    @if ($cell->is_marked)
                        <span class="ml-1 shrink-0 text-green-600 dark:text-green-400" aria-hidden="true">✓</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
