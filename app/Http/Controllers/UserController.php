<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Permissions as P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(P::USERS_MANAGE);

        $users = User::with('roles')
            ->when($request->search, fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', '%' . $request->search . '%')
                   ->orWhere('email', 'like', '%' . $request->search . '%');
            }))
            ->when($request->role, fn ($q) => $q->role($request->role))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $this->authorize(P::USERS_MANAGE);
        $roles = Role::orderBy('name')->pluck('name');
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorize(P::USERS_MANAGE);

        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'roles'                 => 'required|array|min:1',
            'roles.*'               => 'string|exists:roles,name',
            'status'                => 'required|in:active,inactive',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'status'   => $data['status'],
        ]);
        $user->syncRoles($data['roles']);

        return redirect()->route('users.index')->with('success', __('user_management.created'));
    }

    public function edit(User $user)
    {
        $this->authorize(P::USERS_MANAGE);
        $user->load('roles');
        $roles = Role::orderBy('name')->pluck('name');
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize(P::USERS_MANAGE);

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'roles'   => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'status'  => 'required|in:active,inactive',
        ]);

        // Safety: cannot deactivate own account
        if ($user->id === Auth::id() && $data['status'] === 'inactive') {
            return back()->withErrors(['status' => __('user_management.cannot_self_deactivate')])->withInput();
        }

        // Safety: cannot strip the last admin role
        $currentRoles = $user->getRoleNames()->toArray();
        $newRoles     = $data['roles'];
        if (in_array('admin', $currentRoles) && !in_array('admin', $newRoles)) {
            $otherActiveAdmins = User::role('admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();
            if ($otherActiveAdmins === 0) {
                return back()->withErrors(['roles' => __('user_management.last_admin')])->withInput();
            }
        }

        $user->update([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'status' => $data['status'],
        ]);
        $user->syncRoles($newRoles);

        return redirect()->route('users.index')->with('success', __('user_management.updated'));
    }

    public function destroy(User $user)
    {
        $this->authorize(P::USERS_MANAGE);

        abort_if($user->id === Auth::id(), 403, __('user_management.cannot_self_delete'));

        if ($user->hasRole('admin')) {
            $otherAdmins = User::role('admin')->where('id', '!=', $user->id)->count();
            abort_if($otherAdmins === 0, 403, __('user_management.last_admin'));
        }

        if ($user->student()->exists() || $user->staff()->exists()) {
            return back()->with('error', __('user_management.linked_profile'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', __('user_management.deleted'));
    }

    public function toggleStatus(User $user)
    {
        $this->authorize(P::USERS_MANAGE);

        abort_if($user->id === Auth::id(), 403, __('user_management.cannot_self_deactivate'));

        if ($user->hasRole('admin') && $user->status === 'active') {
            $activeAdmins = User::role('admin')->where('status', 'active')->count();
            abort_if($activeAdmins <= 1, 403, __('user_management.last_admin'));
        }

        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', __('user_management.status_updated'));
    }

    public function resetPassword(User $user)
    {
        $this->authorize(P::USERS_MANAGE);

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', __('user_management.password_reset_sent'));
    }
}
