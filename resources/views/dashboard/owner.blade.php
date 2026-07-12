<x-app-layout>
    <x-slot name="title">{{ __('owner.dashboard_title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('owner.dashboard_title') }}</h1>
            <p class="kia-page-sub">{{ __('owner.consolidated') }}</p>
        </div>
        <a href="{{ route('owner.branches.index') }}" class="btn btn-primary">{{ __('owner.manage_branches') }}</a>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    {{-- KPI Stats --}}
    <div class="kia-stats">
        <div class="kia-stat">
            <div class="kia-stat-icon royal">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('owner.total_branches') }}</div>
            <div class="kia-stat-value">{{ number_format($totals['branches']) }}</div>
            <span class="pill pill-ok">{{ $totals['active'] }} {{ __('owner.active_branches') }}</span>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon ok">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('owner.total_students') }}</div>
            <div class="kia-stat-value">{{ number_format($totals['students']) }}</div>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon gold">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('owner.total_revenue') }}</div>
            <div class="kia-stat-value">${{ number_format($totals['revenue'], 2) }}</div>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon warn">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('owner.total_outstanding') }}</div>
            <div class="kia-stat-value">${{ number_format($totals['outstanding'], 2) }}</div>
        </div>
    </div>

    {{-- Branch comparison --}}
    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('owner.comparison_title') }}</h2></div>
        <div class="kia-card-body">
            @if(!$year)
                <div class="kia-empty"><h3>{{ __('owner.no_active_year') }}</h3></div>
            @else
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead><tr>
                        <th>{{ __('owner.col_branch') }}</th>
                        <th>{{ __('owner.col_status') }}</th>
                        <th>{{ __('owner.col_enrolled') }}</th>
                        <th>{{ __('owner.col_attendance') }}</th>
                        <th>{{ __('owner.col_revenue') }}</th>
                        <th>{{ __('owner.col_outstanding') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach($branchRows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('owner.branches.edit', $row['branch']) }}" style="font-weight:600;color:var(--royal);">
                                    {{ $row['branch']->name_km ?: $row['branch']->name_en }}
                                </a>
                                <span class="mono" style="font-size:.75rem;color:var(--muted);margin-left:6px;">{{ $row['branch']->code }}</span>
                            </td>
                            <td>
                                <span class="pill {{ $row['branch']->is_active ? 'pill-ok' : 'pill-bad' }}">
                                    {{ $row['branch']->is_active ? __('branches.status_active') : __('branches.status_suspended') }}
                                </span>
                            </td>
                            <td>{{ number_format($row['enrolled']) }}</td>
                            <td>{{ $row['attendance_rate'] !== null ? $row['attendance_rate'] . '%' : '—' }}</td>
                            <td>${{ number_format($row['revenue'], 2) }}</td>
                            <td>${{ number_format($row['outstanding'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
