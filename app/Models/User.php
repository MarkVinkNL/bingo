<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use App\Models\Friend;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'oauth_provider',
        'role',
        'blocked',
    ];

    /**
     * Whether this user signs in via an OAuth provider (e.g. Google, GitHub).
     * Such users typically should not edit password in settings.
     */
    public function usesOAuth(): bool
    {
        return $this->oauth_provider !== null && $this->oauth_provider !== '';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'blocked' => 'boolean',
        ];
    }

    public function bingoCards(): HasMany
    {
        return $this->hasMany(BingoCard::class);
    }

    public function friendshipsSent(): HasMany
    {
        return $this->hasMany(Friend::class, 'sender_id');
    }

    public function friendshipsReceived(): HasMany
    {
        return $this->hasMany(Friend::class, 'receiver_id');
    }

    /**
     * Users that are friends with this user (accepted only), excluding blocked users.
     *
     * @return Collection<int, User>
     */
    public function friends(): Collection
    {
        $ids = Friend::query()
            ->where('status', \App\Enums\FriendStatus::Accepted)
            ->where(function ($q) {
                $q->where('sender_id', $this->id)->orWhere('receiver_id', $this->id);
            })
            ->get()
            ->map(fn (Friend $f) => $f->sender_id === $this->id ? $f->receiver_id : $f->sender_id)
            ->unique()
            ->values()
            ->all();

        return User::query()
            ->whereIn('id', $ids)
            ->where('blocked', false)
            ->orderBy('name')
            ->get();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::Superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isBlocked(): bool
    {
        return (bool) $this->blocked;
    }

    /**
     * Whether this user can access the Filament admin panel.
     * Only admin and superadmin roles may access.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isBlocked()) {
            return false;
        }

        return $this->role?->canAccessPanel() ?? false;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
