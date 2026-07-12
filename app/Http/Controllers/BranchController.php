<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    /** Owner-only: switch which branch the session currently operates in. */
    public function switch(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $data = $request->validate(['branch_id' => 'required|exists:branches,id']);
        $request->session()->put('current_branch_id', (int) $data['branch_id']);

        return back()->with('success', __('Branch switched.'));
    }
}
