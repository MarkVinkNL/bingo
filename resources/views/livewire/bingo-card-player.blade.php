<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-3">
      <flux:heading size="xl" level="1" class="!text-6xl">{{ $card->bingoSubject->name ?? __('Bingo Card') }}</flux:heading>
      @if ($card->isCompleted())
        <div class="flex flex-col gap-0.5">
          <flux:badge color="green" size="lg">{{ __('Completed') }}</flux:badge>
          <flux:text variant="subtle" class="text-xs">
            {{ __('Completed on :date', ['date' => $card->completed_at->translatedFormat('d M Y, H:i')]) }}
          </flux:text>
        </div>
      @endif
      @if ($shareUrl)
        <flux:button variant="ghost" icon="share" size="sm" aria-label="{{ __('Share card') }}" wire:click="$set('showShareModal', true)" />
      @endif
    </div>
    <flux:link :href="route('bingo.index')" variant="outline" wire:navigate>
      {{ __('Back to lobby') }}
    </flux:link>
  </div>

  @if (!empty($completedBingoLines))
    <flux:callout variant="success" icon="check-badge" :heading="__('Bingo!')"
      :text="__('Completed lines: :lines', ['lines' => implode(', ', $completedBingoLines)])" />
  @endif

  @if ($shareUrl && $showShareModal)
    <flux:modal wire:model="showShareModal" class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Share card') }}</flux:heading>
      <flux:text variant="subtle" class="text-sm">
        {{ __('Anyone with this link can view your card (view only).') }}
      </flux:text>
      <div class="flex flex-wrap items-center gap-2" x-data="{ copied: false }">
        <input type="text" readonly value="{{ $shareUrl }}" class="flex-1 min-w-[200px] rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"
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
          <flux:button variant="outline" type="button" icon="arrow-path" wire:click="regenerateShareToken" wire:confirm="{{ __('Regenerating will invalidate the current link. Continue?') }}">
            {{ __('Regenerate link') }}
          </flux:button>
        @endif
      </div>
      <div class="flex items-center gap-3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&amp;data={{ urlencode($shareUrl) }}" alt="{{ __('QR code for share link') }}" class="size-40 rounded border border-zinc-200 dark:border-zinc-600" width="160" height="160" />
        <flux:text variant="subtle" class="text-sm">{{ __('Scan to open the shared card') }}</flux:text>
      </div>
    </flux:modal>
  @endif

  @if ($card && $card->bingoCardCells->isNotEmpty())
    <div class="inline-grid gap-2" style="grid-template-columns: repeat({{ $card->grid_size }}, minmax(0, 1fr));">
      @foreach ($card->bingoCardCells as $cell)
        @if ($card->isCompleted())
          <div wire:key="cell-{{ $cell->id }}"
            class="flex min-h-[4rem] items-center justify-center rounded-xl border p-4 text-center text-sm font-medium {{ $cell->is_marked
                ? 'border-green-500 bg-green-50 text-green-800 dark:border-green-500/50 dark:bg-green-500/20 dark:text-green-400'
                : 'border-neutral-200 bg-neutral-50 dark:border-neutral-700 dark:bg-white/5' }}">
            <span>{{ $cell->bingoCellValue?->value ?? '—' }}</span>
            @if ($cell->is_marked)
              <span class="ms-1 text-green-600 dark:text-green-400" aria-hidden="true">✓</span>
            @endif
          </div>
        @else
          <button type="button" wire:key="cell-{{ $cell->id }}" wire:click="toggleCell({{ $cell->position }})"
            class="flex min-h-[4rem] items-center justify-center rounded-xl border p-4 text-center text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 {{ $cell->is_marked
                ? 'border-green-500 bg-green-50 text-green-800 dark:border-green-500/50 dark:bg-green-500/20 dark:text-green-400'
                : 'border-neutral-200 bg-neutral-50 hover:border-neutral-300 dark:border-neutral-700 dark:bg-white/5 dark:hover:border-neutral-600' }}">
            <span>{{ $cell->bingoCellValue?->value ?? '—' }}</span>
            @if ($cell->is_marked)
              <span class="ms-1 text-green-600 dark:text-green-400" aria-hidden="true">✓</span>
            @endif
          </button>
        @endif
      @endforeach
    </div>
  @endif
</div>

@once
  <script>
    document.addEventListener('livewire:init', function() {
      Livewire.on('bingo', function() {
        if (typeof confetti !== 'function') return;
        var delay = 280;
        for (var i = 0; i < 3; i++) {
          (function(n) {
            var x = 0.25 + Math.random() * 0.5;
            var y = 0.25 + Math.random() * 0.5;
            setTimeout(function() {
              confetti({
                particleCount: 400,
                spread: 360,
                origin: { x: x, y: y },
                disableForReducedMotion: true
              });
            }, n * delay);
          })(i);
        }
      });
    });
  </script>
@endonce

@if ($card->isCompleted())
  <script>
    (function() {
      if (typeof confetti !== 'function') return;
      var delay = 280;
      for (var i = 0; i < 3; i++) {
        (function(n) {
          var x = 0.25 + Math.random() * 0.5;
          var y = 0.25 + Math.random() * 0.5;
          setTimeout(function() {
            confetti({
              particleCount: 400,
              spread: 360,
              origin: { x: x, y: y },
              disableForReducedMotion: true
            });
          }, n * delay);
        })(i);
      }
      var intervalId = setInterval(function() {
        if (typeof confetti !== 'function') {
          clearInterval(intervalId);
          return;
        }
        confetti({
          particleCount: 80,
          spread: 100,
          origin: {
            x: Math.random(),
            y: Math.random() * 0.6
          },
          disableForReducedMotion: true
        });
      }, 2000);
      document.addEventListener('livewire:navigated', function clearConfetti() {
        clearInterval(intervalId);
        document.removeEventListener('livewire:navigated', clearConfetti);
      });
    })();
  </script>
@endif
