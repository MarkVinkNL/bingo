<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotBlocked
{
    /**
     * If the authenticated user is blocked, log them out and redirect to login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBlocked()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => __('Your account has been blocked. Please contact an administrator.')]);
        }

        return $next($request);
    }
}
