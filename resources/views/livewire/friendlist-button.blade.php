<div>
  <div class="fixed bottom-6 right-6 z-40">
    <flux:button variant="primary" icon="user-group" size="base"
      class="rounded-full shadow-lg"
      aria-label="{{ __('Friends') }}"
      wire:click="openFriendsModal" />
  </div>

  <flux:modal name="friends-list" wire:model="showFriendsModal" class="sm:max-w-md">
    <div class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Friends') }}</flux:heading>

      @php
        $friends = $this->getFriends();
        $pendingReceived = $this->getPendingReceived();
      @endphp

      @if ($pendingReceived->isNotEmpty())
        <section>
          <flux:heading size="sm" level="3" class="mb-2">{{ __('Pending requests') }}</flux:heading>
          <ul class="flex flex-col gap-2">
            @foreach ($pendingReceived as $friend)
              <li class="flex items-center justify-between gap-2 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                <flux:text>{{ $friend->sender->name }}</flux:text>
                <flux:button variant="primary" size="sm" wire:click="acceptRequest({{ $friend->id }})">
                  {{ __('Accept') }}
                </flux:button>
              </li>
            @endforeach
          </ul>
        </section>
      @endif

      <section>
        <flux:heading size="sm" level="3" class="mb-2">{{ __('Your friends') }}</flux:heading>
        @if ($friends->isEmpty())
          <flux:text variant="subtle">{{ __('No friends yet. Use Add to search for users and send a request.') }}</flux:text>
        @else
          <ul class="flex flex-col gap-2">
            @foreach ($friends as $friend)
              <li class="flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                <flux:avatar :name="$friend->name" size="sm" />
                <flux:text>{{ $friend->name }}</flux:text>
              </li>
            @endforeach
          </ul>
        @endif
      </section>

      <div class="flex justify-between">
        <flux:button variant="ghost" wire:click="closeFriendsModal">{{ __('Close') }}</flux:button>
        <flux:button variant="outline" icon="plus" size="sm" wire:click="openAddFriendModal">
          {{ __('Add') }}
        </flux:button>
      </div>
    </div>
  </flux:modal>

  <flux:modal name="add-friend" wire:model="showAddFriendModal" class="sm:max-w-md">
    <div class="flex flex-col gap-4">
      <flux:heading size="lg" level="2">{{ __('Add friend') }}</flux:heading>
      <flux:text variant="subtle">{{ __('Search by name and send a friend request.') }}</flux:text>

      <flux:field>
        <flux:label for="friend-search">{{ __('Search users') }}</flux:label>
        <flux:input id="friend-search" type="text" wire:model.live.debounce.300ms="searchQuery"
          placeholder="{{ __('Name') }}" wire:keydown.enter.prevent="searchUsers" />
      </flux:field>

      <flux:button variant="outline" wire:click="searchUsers">{{ __('Search') }}</flux:button>

      @if ($searchQuery !== '')
        @if ($searchResults->isEmpty())
          <flux:text variant="subtle">{{ __('No users found.') }}</flux:text>
        @else
          <ul class="flex flex-col gap-2">
            @foreach ($searchResults as $user)
              <li class="flex items-center justify-between gap-2 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-700">
                <div class="flex items-center gap-2">
                  <flux:avatar :name="$user->name" size="sm" />
                  <flux:text>{{ $user->name }}</flux:text>
                </div>
                <flux:button variant="primary" size="sm" wire:click="sendRequest({{ $user->id }})">
                  {{ __('Send request') }}
                </flux:button>
              </li>
            @endforeach
          </ul>
        @endif
      @endif

      <div class="flex justify-end">
        <flux:button variant="ghost" wire:click="closeAddFriendModal">{{ __('Close') }}</flux:button>
      </div>
    </div>
  </flux:modal>
</div>
