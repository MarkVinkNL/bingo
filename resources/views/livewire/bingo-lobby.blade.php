<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <flux:heading size="xl" level="1">{{ __('Choose a subject') }}</flux:heading>

    @if($myCards->isNotEmpty())
        <section>
            <flux:heading size="lg" level="2" class="mb-3">{{ __('My cards') }}</flux:heading>
            <ul class="flex flex-wrap gap-2">
                @foreach($myCards as $card)
                    <li>
                        <flux:link
                            :href="route('bingo.card', $card->uuid)"
                            variant="outline"
                            wire:navigate
                        >
                            {{ $card->bingoSubject->name }} ({{ $card->grid_size }}×{{ $card->grid_size }})
                        </flux:link>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <flux:heading size="lg" level="2" class="mb-3">{{ __('Subjects') }}</flux:heading>

        @if($subjectsWithCards->isEmpty())
            <flux:text variant="subtle">{{ __('No active subjects yet.') }}</flux:text>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($subjectsWithCards as $item)
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 dark:border-neutral-700"
                        wire:key="subject-{{ $item->subject->id }}"
                    >
                        <flux:heading size="lg" level="3">{{ $item->subject->name }}</flux:heading>
                        <flux:text variant="subtle">
                            {{ __('Grid') }}: {{ $item->gridSizeLabel }}
                        </flux:text>
                        @if($item->existingCard)
                            <flux:link
                                :href="route('bingo.card', $item->existingCard->uuid)"
                                variant="primary"
                                class="w-full justify-center"
                                wire:navigate
                            >
                                {{ __('Open your card') }}
                            </flux:link>
                        @else
                            <flux:link
                                :href="route('bingo.play', $item->subject->slug)"
                                variant="outline"
                                class="w-full justify-center"
                                wire:navigate
                            >
                                {{ __('Play') }}
                            </flux:link>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
