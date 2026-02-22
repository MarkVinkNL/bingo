<?php

namespace App\Livewire;

use App\Enums\FriendStatus;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FriendlistButton extends Component
{
    public bool $showFriendsModal = false;

    public bool $showAddFriendModal = false;

    public string $searchQuery = '';

    /**
     * @var Collection<int, User>
     */
    public Collection $searchResults;

    public function mount(): void
    {
        $this->searchResults = collect();
    }

    /**
     * @return Collection<int, User>
     */
    public function getFriends(): Collection
    {
        $user = Auth::user();
        if ($user === null) {
            return collect();
        }

        return $user->friends();
    }

    /**
     * Pending requests received by the current user.
     *
     * @return Collection<int, Friend>
     */
    public function getPendingReceived(): Collection
    {
        $userId = Auth::id();
        if ($userId === null) {
            return collect();
        }

        return Friend::query()
            ->where('receiver_id', $userId)
            ->pending()
            ->with('sender')
            ->get()
            ->filter(fn (Friend $f) => $f->sender && ! $f->sender->isBlocked());
    }

    public function openFriendsModal(): void
    {
        $this->showFriendsModal = true;
        $this->showAddFriendModal = false;
    }

    public function closeFriendsModal(): void
    {
        $this->showFriendsModal = false;
    }

    public function openAddFriendModal(): void
    {
        $this->showFriendsModal = false;
        $this->showAddFriendModal = true;
        $this->searchQuery = '';
        $this->searchResults = collect();
    }

    public function closeAddFriendModal(): void
    {
        $this->showAddFriendModal = false;
        $this->searchQuery = '';
        $this->searchResults = collect();
        $this->showFriendsModal = true;
    }

    public function updatedSearchQuery(): void
    {
        $this->searchUsers();
    }

    public function searchUsers(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->searchResults = collect();

            return;
        }

        $query = trim($this->searchQuery);
        if ($query === '') {
            $this->searchResults = collect();

            return;
        }

        $linkedUserIds = Friend::query()
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->pluck('sender_id')
            ->merge(
                Friend::query()
                    ->where(function ($q) use ($user) {
                        $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
                    })
                    ->pluck('receiver_id')
            )
            ->unique()
            ->all();

        $this->searchResults = User::query()
            ->where('id', '!=', $user->id)
            ->where('blocked', false)
            ->whereNotIn('id', $linkedUserIds)
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    public function sendRequest(int $receiverId): void
    {
        $user = Auth::user();
        if ($user === null) {
            return;
        }

        Friend::query()->firstOrCreate(
            [
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
            ],
            ['status' => FriendStatus::Pending]
        );
        $this->searchUsers();
    }

    public function acceptRequest(int $friendId): void
    {
        $userId = Auth::id();
        if ($userId === null) {
            return;
        }

        Friend::query()
            ->where('id', $friendId)
            ->where('receiver_id', $userId)
            ->where('status', FriendStatus::Pending)
            ->update(['status' => FriendStatus::Accepted]);
    }

    public function render()
    {
        return view('livewire.friendlist-button');
    }
}
