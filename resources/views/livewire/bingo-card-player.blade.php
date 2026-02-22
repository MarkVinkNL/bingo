<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl" @if(!empty($otherParticipantCards)) wire:poll.1s @endif>
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
      @if ($shareUrl)
        <flux:button variant="ghost" icon="share" size="sm" aria-label="{{ __('Share card') }}"
          wire:click="$set('showShareModal', true)" />
      @endif
      @can('delete', $card)
        <flux:button variant="ghost" color="red" icon="trash" size="sm" aria-label="{{ __('Remove card') }}"
          wire:click="removeCard" wire:confirm="{{ __('Remove this bingo card? This cannot be undone.') }}" />
      @endcan
    </div>
    <flux:link :href="route('bingo.index')" variant="outline" wire:navigate>
      {{ __('Back to lobby') }}
    </flux:link>
  </div>

  @if (!empty($completedBingoLines))
    <flux:callout variant="success" icon="check-badge" :heading="__('Bingo!')"
      :text="__('Completed lines: :lines', ['lines' => implode(', ', $completedBingoLines)])" />
  @endif

  @if ($battleWinner)
    <flux:callout variant="success" icon="trophy"
      :heading="$battleWinner->id === auth()->id() ? __('You won the battle!') : __('Battle won')"
      :text="$battleWinner->id === auth()->id() ? __('You were the first to get bingo.') : __(':name was the first to get bingo.', ['name' => $battleWinner->name])" />
  @endif

  @if ($shareUrl && $showShareModal)
    <flux:modal wire:model="showShareModal" class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Share card') }}</flux:heading>
      <flux:text variant="subtle" class="text-sm">
        {{ __('Anyone with this link can view your card (view only).') }}
      </flux:text>
      <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false }">
        <input type="text" readonly value="{{ $shareUrl }}"
          class="flex-1 min-w-[200px] rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
          x-ref="shareInput" />
        <flux:button variant="primary" type="button" icon="clipboard-document"
          @click="
            (async () => {
              try {
                var url = $refs.shareInput.value;
                if (navigator.clipboard && window.isSecureContext) {
                  await navigator.clipboard.writeText(url);
                } else {
                  $refs.shareInput.select();
                  document.execCommand('copy');
                }
                copied = true;
                setTimeout(() => copied = false, 2000);
              } catch (e) {
                $refs.shareInput.select();
                document.execCommand('copy');
                copied = true;
                setTimeout(() => copied = false, 2000);
              }
            })();
          ">
          <span x-text="copied ? '{{ __('Copied!') }}' : '{{ __('Copy link') }}'"></span>
        </flux:button>
        @if (!$card->isCompleted())
          <flux:button variant="outline" type="button" icon="arrow-path" wire:click="regenerateShareToken"
            wire:confirm="{{ __('Regenerating will invalidate the current link. Continue?') }}">
            {{ __('Regenerate link') }}
          </flux:button>
        @endif
      </div>
      <div class="flex items-center gap-3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;data={{ urlencode($shareUrl) }}"
          alt="{{ __('QR code for share link') }}" class="size-40 rounded border border-zinc-200 dark:border-zinc-600"
          width="160" height="160" />
        <flux:text variant="subtle" class="text-sm">{{ __('Scan to open the shared card') }}</flux:text>
      </div>
    </flux:modal>
  @endif

  <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
    @if ($card && $card->bingoCardCells->isNotEmpty())
      <div class="bingo-card">
        <div class="bingo-card__grid" style="grid-template-columns: repeat({{ $card->grid_size }}, minmax(0, 1fr));">
          @foreach ($card->bingoCardCells as $cell)
            @if ($card->isCompleted())
              <div wire:key="cell-{{ $cell->id }}"
                class="bingo-card__cell {{ $cell->is_marked ? 'bingo-card__cell--marked' : 'bingo-card__cell--default' }}">
                <span>{{ $cell->bingoCellValue?->value ?? '—' }}</span>
                @if ($cell->is_marked)
                  <span class="bingo-card__cell-check" aria-hidden="true">✓</span>
                @endif
              </div>
            @else
              <div role="button" tabindex="0" wire:key="cell-{{ $cell->id }}"
                wire:click="toggleCell({{ $cell->position }})"
                class="bingo-card__cell bingo-card__cell--interactive {{ $cell->is_marked ? 'bingo-card__cell--marked' : 'bingo-card__cell--default' }}">
                <span>{{ $cell->bingoCellValue?->value ?? '—' }}</span>
                @if ($cell->is_marked)
                  <span class="bingo-card__cell-check" aria-hidden="true">✓</span>
                @endif
              </div>
            @endif
          @endforeach
        </div>
      </div>
    @endif

    @if (!empty($otherParticipantCards))
      <aside class="flex shrink-0 flex-col gap-4 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700 lg:min-w-[200px]">
        <flux:heading size="lg" level="2">{{ __('Battle') }}</flux:heading>
        <flux:text variant="subtle" class="text-sm">{{ __('Other players') }}</flux:text>
        @foreach ($otherParticipantCards as $index => $participant)
          <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-center gap-2">
              <flux:text class="font-medium">{{ $participant['userName'] }}</flux:text>
              @if ($participant['isWinner'] ?? false)
                <flux:badge color="green" size="sm">{{ __('Winner') }}</flux:badge>
              @endif
            </div>
            <div class="bingo-card bingo-card--mini">
              <div class="bingo-card__grid" style="grid-template-columns: repeat({{ $participant['gridSize'] }}, minmax(0, 1fr));">
                @for ($pos = 0; $pos < $participant['gridSize'] * $participant['gridSize']; $pos++)
                  @php $marked = $participant['cells'][$pos] ?? false; @endphp
                  <div wire:key="opp-{{ $index }}-{{ $pos }}"
                    class="bingo-card__cell {{ $marked ? 'bingo-card__cell--marked' : 'bingo-card__cell--default' }}">
                    @if ($marked)
                      <span class="bingo-card__cell-check" aria-hidden="true">✓</span>
                    @endif
                  </div>
                @endfor
              </div>
            </div>
          </div>
        @endforeach
      </aside>
    @endif
  </div>
</div>

@if ($card->isCompleted())
  <script>
    document.dispatchEvent(new CustomEvent('bingo-complete'));
  </script>
@endif
