<x-app-layout>
    <x-slot name="title">{{ __('Staff') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Staff') }}</h1>
            <p class="kia-page-sub">{{ $staff->total() }} {{ __('members') }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('staff.export-excel', request()->query()) }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('staff.export-pdf', request()->query()) }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            @can('staff.create')
            <a href="{{ route('staff.create') }}" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Staff') }}
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="kia-filter-bar">
        <input type="search" name="search" class="form-control" placeholder="{{ __('Search name, email…') }}" value="{{ request('search') }}">
        @if($departments->count())
        <select name="department" class="form-control" style="min-width:160px;">
            <option value="">{{ __('All departments') }}</option>
            @foreach($departments as $dept)
            <option value="{{ $dept }}" {{ request('department') == $dept ? 'selected' : '' }}>{{ $dept }}</option>
            @endforeach
        </select>
        @endif
        <button type="submit" class="btn btn-outline btn-sm">{{ __('Filter') }}</button>
        @if(request()->hasAny(['search','department']))
        <a href="{{ route('staff.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
        @endif
    </form>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Position') }}</th>
                        <th>{{ __('Department') }}</th>
                        <th>{{ __('Joined') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                    <tr>
                        <td>
                            <div class="student-cell">
                                <div class="kia-avatar" style="width:34px;height:34px;font-size:.75rem;border-radius:50%;">
                                    {{ strtoupper(substr($member->user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;">{{ $member->user->name }}</div>
                                    <div style="font-size:.78rem;color:var(--muted);">{{ $member->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="mono">{{ $member->staff_code }}</span></td>
                        <td>
                            @foreach($member->user->getRoleNames() as $role)
                            <span class="pill role-{{ $role }}">{{ $role }}</span>
                            @endforeach
                        </td>
                        <td>{{ $member->position ?? '—' }}</td>
                        <td>{{ $member->department ?? '—' }}</td>
                        <td>{{ $member->joined_at?->format('M Y') ?? '—' }}</td>
                        <td style="text-align:right;">
                            <div class="d-flex gap-2" style="justify-content:flex-end;">
                                <a href="{{ route('staff.show', $member) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a>
                                @can('staff.edit')
                                <a href="{{ route('staff.edit', $member) }}" class="btn btn-outline btn-sm">{{ __('Edit') }}</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="kia-empty">
                                <h3>{{ __('No staff members found') }}</h3>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
