<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Usage: ->middleware('role:admin') or ('role:admin,staff')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login')->with('error', 'Please log in first.');
        }

        // Flatten comma-separated roles: role:admin,staff
        $allowed = [];
        foreach ($roles as $r) {
            foreach (explode(',', $r) as $part) {
                $allowed[] = trim($part);
            }
        }

        if (! in_array($user->role, $allowed, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You are not authorized to access this resource.'], 403);
            }
            abort(403, 'You are not authorized to access this page.');
        }

        if ($user->status !== 'active') {
            if ($request->expectsJson()) {
                $token = $user->currentAccessToken();
                if ($token && method_exists($token, 'delete')) $token->delete();
                return response()->json(['message' => 'Your account is deactivated. Contact the administrator.'], 403);
            }
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account is deactivated. Contact the administrator.');
        }

        return $next($request);
    }
}
