<x-app-layout>
    <x-slot name="title">{{ __('Users') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Users') }}</h1>
            <p class="kia-page-sub">{{ $users->total() }} {{ __('total') }}</p>
        </div>
        @can('users.manage')
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            {{ __('Add User') }}
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="kia-filter-bar">
        <input type="search" name="search" class="form-control" placeholder="{{ __('Search name or email…') }}" value="{{ request('search') }}">
        <select name="role" class="form-control" style="min-width:150px;">
            <option value="">{{ __('All roles') }}</option>
            @foreach($roles as $role)
            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" style="min-width:130px;">
            <option value="">{{ __('All statuses') }}</option>
            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
        </select>
        <button type="submit" class="btn btn-outline btn-sm">{{ __('Filter') }}</button>
        @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('users.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Roles') }}</th>
                        <th>{{ __('Linked Profile') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="font-weight:600;">{{ $user->name }}</div>
                        </td>
                        <td><span class="mono" style="font-size:.82rem;">{{ $user->email }}</span></td>
                        <td>
                            @foreach($user->roles as $role)
                            <span class="pill pill-royal">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($user->student)
                                <span class="pill pill-ok">{{ __('Student') }}: {{ $user->student->name_en }}</span>
                            @elseif($user->staff)
                                <span class="pill pill-muted">{{ __('Staff') }}: {{ $user->staff->name ?? $user->name }}</span>
                            @else
                                <span style="color:var(--muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="pill pill-ok">{{ __('Active') }}</span>
                            @else
                                <span class="pill pill-danger">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-right" style="white-space:nowrap;">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline">{{ __('Edit') }}</a>

                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.toggle-status', $user) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-warn' : 'btn-ghost' }}"
                                    onclick="return confirm('{{ $user->status === 'active' ? __('Deactivate this user?') : __('Activate this user?') }}')">
                                    {{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('users.reset-password', $user) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-ghost">{{ __('Reset Password') }}</button>
                            </form>

                            <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('{{ __('Delete this user? This cannot be undone.') }}')">
                                    {{ __('Delete') }}
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">{{ __('No users found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem;">{{ $users->links() }}</div>
    </div>
</x-app-layout>
