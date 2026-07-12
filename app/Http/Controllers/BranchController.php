<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Branch management — every action here is owner-only, enforced by the
 * role:owner middleware on the whole route group (routes/web.php), not by
 * a Permissions constant: this is an architectural tier (owner vs.
 * everyone-else), not a grantable permission. See BranchIsolationTest /
 * OwnerConsoleTest for the 403 coverage.
 */
class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount(['students', 'staff'])->orderBy('id')->get();
        return view('owner.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('owner.branches.create');
    }

    public function store(StoreBranchRequest $request)
    {
        $data = $request->safe()->except('logo');

        if ($file = $request->file('logo')) {
            $data['logo_path'] = $file->store('branches/logos', 'local');
        }

        $branch = Branch::create($data);

        return redirect()->route('owner.branches.index')->with('success', __('branches.created'));
    }

    public function edit(Branch $branch)
    {
        return view('owner.branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        $data = $request->safe()->except('logo');

        if ($file = $request->file('logo')) {
            if ($branch->logo_path) {
                Storage::disk('local')->delete($branch->logo_path);
            }
            $data['logo_path'] = $file->store('branches/logos', 'local');
        }

        $branch->update($data);

        return redirect()->route('owner.branches.index')->with('success', __('branches.updated'));
    }

    /** Reversible suspend/reactivate — never deletes or hides historical data. */
    public function toggleActive(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);

        return back()->with('success', $branch->is_active
            ? __('branches.reactivated', ['name' => $branch->name_en])
            : __('branches.suspended', ['name' => $branch->name_en]));
    }

    /** Gated download of the branch logo (private disk, mirrors PhotoController's pattern). */
    public function logo(Branch $branch)
    {
        abort_unless($branch->logo_path && Storage::disk('local')->exists($branch->logo_path), 404);

        return response()->file(Storage::disk('local')->path($branch->logo_path));
    }

    public function admins(Branch $branch)
    {
        $admins = $branch->admins()->orderBy('name')->get();

        return view('owner.branches.admins', compact('branch', 'admins'));
    }

    /**
     * Appoint an admin for this branch — either an existing user (found by
     * email) or a brand-new one created inline. Either way the user ends up
     * with the 'admin' role and branch_id = this branch (moving them out of
     * whatever branch they were in before, if any — appointment is a move,
     * not a copy).
     */
    public function appointAdmin(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'existing_email' => ['nullable', 'email', 'required_without_all:new_name,new_email'],
            'new_name'       => ['nullable', 'string', 'max:150', 'required_with:new_email'],
            'new_email'      => ['nullable', 'email', 'required_with:new_name', 'unique:users,email'],
            'new_password'   => ['nullable', 'string', 'min:8'],
        ]);

        if (!empty($data['existing_email'])) {
            $user = User::where('email', $data['existing_email'])->first();
            if (!$user) {
                return back()->withErrors(['existing_email' => __('branches.user_not_found')])->withInput();
            }
        } else {
            $user = User::create([
                'name'     => $data['new_name'],
                'email'    => $data['new_email'],
                'password' => Hash::make($data['new_password'] ?? 'password'),
                'status'   => 'active',
            ]);
        }

        $user->forceFill(['branch_id' => $branch->id])->save();
        $user->assignRole('admin');

        return redirect()->route('owner.branches.admins', $branch)
            ->with('success', __('branches.admin_appointed', ['name' => $user->name]));
    }

    /** Revokes the admin role only — never deletes the user or their branch_id. */
    public function removeAdmin(Branch $branch, User $user)
    {
        abort_unless($user->branch_id === $branch->id, 404);

        $user->removeRole('admin');

        return back()->with('success', __('branches.admin_removed', ['name' => $user->name]));
    }

    /** Owner-only: switch which branch the session currently operates in. */
    public function switch(Request $request)
    {
        abort_unless($request->user()->hasRole('owner'), 403);

        $data = $request->validate(['branch_id' => 'required|exists:branches,id']);
        $request->session()->put('current_branch_id', (int) $data['branch_id']);

        return back()->with('success', __('Branch switched.'));
    }
}
