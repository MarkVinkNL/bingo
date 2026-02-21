<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanChangePassword
{
    /**
     * Redirect OAuth users (Google, GitHub) away from password settings;
     * they do not use a password to sign in.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->usesOAuth()) {
            return redirect()->route('profile.edit');
        }

        return $next($request);
    }
}
