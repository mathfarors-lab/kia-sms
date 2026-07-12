<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks a suspended branch's own users from doing anything — login succeeds
 * (credentials are still valid) but every authenticated page after that
 * renders the blocked-access notice instead. This is deliberately simpler
 * than distinguishing reads from writes: "suspended" means the branch's
 * whole operation pauses, not a partial read-only mode. Nothing is deleted —
 * reactivating the branch restores full access on the very next request.
 *
 * The owner is never blocked (not "of" any single branch — needs to reach a
 * suspended branch's historical data), and the logout route is always let
 * through so a blocked user isn't locked out of their own sign-out.
 */
class EnsureBranchIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->branch_id && !$user->hasRole('owner') && !$request->routeIs('logout')) {
            $branch = Branch::find($user->branch_id);

            if ($branch && !$branch->is_active) {
                return response()->view('errors.branch-suspended', ['branch' => $branch], 403);
            }
        }

        return $next($request);
    }
}
