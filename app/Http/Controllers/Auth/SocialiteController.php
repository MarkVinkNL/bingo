<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialiteController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'github'];

    public function redirect(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isAllowedProvider($provider)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! $this->isAllowedProvider($provider)) {
            abort(404);
        }

        $socialiteUser = Socialite::driver($provider)->user();
        $user = $this->findOrCreateUser($socialiteUser);

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('bingo.index'));
    }

    private function isAllowedProvider(string $provider): bool
    {
        return in_array(Str::lower($provider), self::ALLOWED_PROVIDERS, true);
    }

    private function findOrCreateUser(SocialiteUser $socialiteUser): User
    {
        $email = $socialiteUser->getEmail();
        if ($email === null || $email === '') {
            abort(403, 'Your account does not provide an email address. Please use a different login method or ensure your provider shares your email.');
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            return $user;
        }

        $name = $socialiteUser->getName()
            ?? $socialiteUser->getNickname()
            ?? explode('@', $email)[0];

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'role' => UserRole::Player,
        ]);
    }
}
