<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckIfActive
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (Auth::guest() || $request->is('mindar')) {
            return $next($request);
        }

        if (Auth::user()->active == 0) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'account' =>
                        'Your account is suspended. Please contact the administrator.',
                ]);
        }

        return $next($request);
    }
}
