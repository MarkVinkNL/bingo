<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    /**
     * If the user is blocked, log them out and redirect to login with an error.
     * Otherwise redirect as usual.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        /** @var Request $request */
        if ($request->user()?->isBlocked()) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('Your account has been blocked. Please contact an administrator.')]);
        }

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->intended(Fortify::redirects('login'));
    }
}
