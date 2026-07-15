<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Requesting another role's dashboard route redirects to your own instead
 * of rendering it. No query in DashboardController actually leaks data —
 * every stat is independently scoped — but seeing another role's dashboard
 * chrome at all is still wrong: a stale bookmark, a role change, or a
 * guessed URL should land you where you belong, not on someone else's screen.
 */
class EnsureOwnDashboard
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (!$user->hasRole($role)) {
            return redirect()->route($user->dashboardRoute());
        }

        return $next($request);
    }
}
