<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;

class SetBranchContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            if ($user->hasRole('owner')) {
                // Owner works "inside" one branch at a time via the topbar
                // switcher; default to the first active branch on first login.
                // A suspended branch stays reachable once explicitly selected —
                // the owner must still be able to review its historical data —
                // only the no-selection-yet fallback prefers an active branch.
                $branchId = $request->session()->get('current_branch_id');

                if (!$branchId || !Branch::where('id', $branchId)->exists()) {
                    $branchId = Branch::where('is_active', true)->orderBy('id')->value('id');
                    $request->session()->put('current_branch_id', $branchId);
                }

                BranchContext::set($branchId);
            } else {
                // Everyone else is locked to their own branch. Users with no
                // branch (legacy/factory) stay unscoped — pre-M1 behavior.
                BranchContext::set($user->branch_id);
            }
        }

        return $next($request);
    }
}
