<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->isActive()) {
            if ($request->expectsJson()) abort(403, 'Your account is deactivated.');
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Your account is deactivated.');
        }
        return $next($request);
    }
}
