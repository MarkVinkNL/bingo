<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">

  @if ($myCards->isNotEmpty() || $pendingBattleInvites->isNotEmpty())
    <section>
      <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="2" class="m-0">{{ __('My Bingo cards') }}</flux:heading>
        @can('create', \App\Models\BingoBattle::class)
          <flux:button variant="outline" icon="bolt" wire:click="openCreateBattleModal">
            {{ __('Start a battle') }}
          </flux:button>
        @endcan
      </div>
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
              @if ($card->battle_id)
                <flux:badge color="zinc" size="sm">{{ __('Battle') }}</flux:badge>
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
        @foreach ($pendingBattleInvites as $invite)
          <div wire:key="invite-{{ $invite->id }}" role="button" tabindex="0"
            class="flex flex-col gap-3 rounded-xl border border-amber-200 p-4 dark:border-amber-700/50 transition-colors hover:border-amber-300 hover:bg-amber-50/50 dark:hover:border-amber-600 dark:hover:bg-amber-900/20 cursor-pointer"
            wire:click="openInviteModal({{ $invite->id }})">
            <div class="flex items-start justify-between gap-2">
              <flux:heading size="lg" level="3" class="min-w-0 flex-1">
                {{ $invite->bingoBattle->bingoSubject->name ?? __('Bingo') }}
              </flux:heading>
              <flux:badge color="amber" size="sm">{{ __('Battle invite') }}</flux:badge>
            </div>
            <flux:text variant="subtle" class="text-sm">
              {{ __('From :name', ['name' => $invite->bingoBattle->createdBy->name ?? '']) }}
            </flux:text>
          </div>
        @endforeach
      </div>
    </section>
  @else
    <section>
      <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl" level="2" class="m-0">{{ __('My Bingo cards') }}</flux:heading>
        @can('create', \App\Models\BingoBattle::class)
          <flux:button variant="outline" icon="bolt" wire:click="openCreateBattleModal">
            {{ __('Start a battle') }}
          </flux:button>
        @endcan
      </div>
      <flux:text variant="subtle">{{ __('You have no bingo cards yet. Make one below or accept a battle invite.') }}</flux:text>
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

  <flux:modal name="create-battle" wire:model="showCreateBattleModal" class="sm:max-w-lg">
    <div class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Start a battle') }}</flux:heading>
      @if ($createBattleStep === 1)
        <flux:text variant="subtle">{{ __('Choose a bingo subject for the battle.') }}</flux:text>
        <flux:field>
          <flux:label for="battle-subject">{{ __('Subject') }}</flux:label>
          <flux:select id="battle-subject" wire:model="selectedBattleSubjectId" placeholder="{{ __('Select subject') }}">
            @foreach ($battleSubjects as $subject)
              <flux:select.option value="{{ $subject->id }}">{{ $subject->name }}</flux:select.option>
            @endforeach
          </flux:select>
          <flux:error name="selectedBattleSubjectId" />
        </flux:field>
        <div class="flex flex-wrap justify-end gap-2">
          <flux:button variant="ghost" wire:click="closeCreateBattleModal">{{ __('Cancel') }}</flux:button>
          <flux:button variant="primary" wire:click="createBattleNextStep">{{ __('Next') }}</flux:button>
        </div>
      @else
        <flux:text variant="subtle">{{ __('Select friends to invite.') }}</flux:text>
        <flux:field>
          <flux:label>{{ __('Friends') }}</flux:label>
          @if ($friends->isEmpty())
            <flux:text variant="subtle">{{ __('You have no friends yet. Add friends from the menu to invite them to battles.') }}</flux:text>
          @else
            <div class="flex flex-col gap-2 max-h-64 overflow-y-auto">
              @foreach ($friends as $friend)
                <label class="flex items-center gap-2 cursor-pointer">
                  <flux:checkbox wire:model="selectedBattleFriendIds" value="{{ $friend->id }}" />
                  <flux:avatar :name="$friend->name" size="sm" />
                  <flux:text>{{ $friend->name }}</flux:text>
                </label>
              @endforeach
            </div>
          @endif
        </flux:field>
        <div class="flex flex-wrap justify-end gap-2">
          <flux:button variant="ghost" wire:click="createBattlePrevStep">{{ __('Back') }}</flux:button>
          <flux:button variant="ghost" wire:click="closeCreateBattleModal">{{ __('Cancel') }}</flux:button>
          <flux:button variant="primary" wire:click="createBattle" wire:loading.attr="disabled">
            {{ __('Create battle') }}
          </flux:button>
        </div>
      @endif
    </div>
  </flux:modal>

  <flux:modal name="battle-invite" wire:model="showInviteModal" class="sm:max-w-md">
    @if ($inviteForModal)
      <div class="flex flex-col gap-4">
        <flux:heading size="lg" level="2">{{ __('Battle invite') }}</flux:heading>
        <flux:text variant="subtle">
          <strong>{{ $inviteForModal->bingoBattle->createdBy->name }}</strong>
          {{ __('invited you to a bingo battle.') }}
        </flux:text>
        <div>
          <flux:heading size="sm" level="3" class="mb-2">{{ __('Subject') }}</flux:heading>
          <flux:text>{{ $inviteForModal->bingoBattle->bingoSubject->name }}</flux:text>
        </div>
        <div>
          <flux:heading size="sm" level="3" class="mb-2">{{ __('Participants') }}</flux:heading>
          <ul class="list-disc list-inside space-y-1">
            <li><flux:text>{{ $inviteForModal->bingoBattle->createdBy->name }} ({{ __('Creator') }})</flux:text></li>
            @foreach ($inviteForModal->bingoBattle->invites as $inv)
              <li><flux:text>{{ $inv->user->name ?? '' }}</flux:text></li>
            @endforeach
          </ul>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
          <flux:button variant="ghost" wire:click="closeInviteModal">{{ __('Close') }}</flux:button>
          <flux:button variant="outline" color="red" wire:click="declineInvite({{ $inviteForModal->id }})">
            {{ __('Decline') }}
          </flux:button>
          <flux:button variant="primary" wire:click="acceptInvite({{ $inviteForModal->id }})" wire:loading.attr="disabled">
            {{ __('Accept') }}
          </flux:button>
        </div>
      </div>
    @endif
  </flux:modal>
</div>
