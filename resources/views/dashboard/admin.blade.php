<x-app-layout>
    <x-slot name="title">{{ __('Admin Dashboard') }}</x-slot>

    <div class="kia-breadcrumb">
        <span>{{ __('Home') }}</span>
        <span class="sep">/</span>
        <span>{{ __('Dashboard') }}</span>
    </div>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Admin Dashboard') }}</h1>
            <p class="kia-page-sub">{{ __('Welcome back,') }} {{ auth()->user()->name }}</p>
        </div>
        <div style="font-size:.8rem;color:var(--muted);">
            {{ now()->format('l, d F Y') }}
        </div>
    </div>

    {{-- Quick actions — top button row, mirrors the desktop app's dashboard toolbar --}}
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
        <a href="{{ route('students.create') }}" class="btn btn-primary">{{ __('+ Add Student') }}</a>
        <a href="{{ route('attendance.index') }}" class="btn btn-outline">{{ __('Mark Attendance') }}</a>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline">{{ __('Collect Fee') }}</a>
    </div>

    {{-- KPI Stats --}}
    <div class="kia-stats">
        <div class="kia-stat">
            <div class="kia-stat-icon royal">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('Total Students') }}</div>
            <div class="kia-stat-value">{{ number_format($stats['total_students']) }}</div>
            <span class="pill pill-ok">{{ $stats['enrolled'] }} {{ __('enrolled') }}</span>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon ok">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('Staff Members') }}</div>
            <div class="kia-stat-value">{{ number_format($stats['total_staff']) }}</div>
            <span class="pill pill-royal">{{ __('Active') }}</span>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon gold">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('Revenue (Month)') }}</div>
            <div class="kia-stat-value">${{ number_format($stats['revenue_month'], 2) }}</div>
            <span class="pill pill-ok">{{ __('admin_dashboard.this_month') }}</span>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon warn">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="9,11 12,14 22,4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('Attendance Today') }}</div>
            <div class="kia-stat-value">{{ $stats['attendance_today'] !== null ? $stats['attendance_today'] . '%' : '—' }}</div>
            <span class="pill {{ $stats['attendance_today'] !== null ? 'pill-ok' : 'pill-muted' }}">
                {{ $stats['attendance_today'] !== null ? __('admin_dashboard.today') : __('admin_dashboard.no_attendance_yet') }}
            </span>
        </div>
    </div>

    {{-- Attendance trend + enrollment by class --}}
    <div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:16px;">
        <div class="kia-card">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ __('Attendance Trend (last 7 days)') }}</h2>
            </div>
            <div class="kia-card-body">
                <div style="position:relative;height:280px;">
                    <canvas data-kia-chart="{{ json_encode([
                        'type' => 'line',
                        'data' => [
                            'labels' => array_column($attendanceTrend, 'label'),
                            'datasets' => [[
                                'label' => __('Attendance %'),
                                'data' => array_column($attendanceTrend, 'rate'),
                                'borderColor' => '#5B6EF5',
                                'backgroundColor' => 'rgba(91,110,245,.15)',
                                'fill' => true,
                                'tension' => .3,
                                'pointRadius' => 4,
                            ]],
                        ],
                        'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['min' => 0, 'max' => 100]]],
                    ]) }}"></canvas>
                </div>
            </div>
        </div>

        <div class="kia-card">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ __('Enrollment by Class') }}</h2>
            </div>
            <div class="kia-card-body">
                <div style="position:relative;height:280px;">
                    <canvas data-kia-chart="{{ json_encode([
                        'type' => 'bar',
                        'data' => [
                            'labels' => array_column($enrollmentByClass, 'class_name'),
                            'datasets' => [[
                                'label' => __('Students'),
                                'data' => array_column($enrollmentByClass, 'student_count'),
                                'backgroundColor' => '#7B5CF0',
                                'borderRadius' => 4,
                                'maxBarThickness' => 34,
                            ]],
                        ],
                        'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
                    ]) }}"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Fee collection status --}}
    <div class="kia-card" style="margin-bottom:24px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Fee Collection Status') }}</h2>
        </div>
        <div class="kia-card-body" style="max-width:420px;margin:0 auto;">
            <div style="position:relative;height:240px;">
                <canvas data-kia-chart="{{ json_encode([
                    'type' => 'pie',
                    'data' => [
                        'labels' => [__('Collected'), __('Pending')],
                        'datasets' => [[
                            'data' => [$feeCollected, $feePending],
                            'backgroundColor' => ['#1E8A55', '#E5A64A'],
                        ]],
                    ],
                    'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['position' => 'right']]],
                ]) }}"></canvas>
            </div>
        </div>
    </div>

    {{-- More actions --}}
    <div class="kia-card" style="margin-bottom:24px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('More Actions') }}</h2>
        </div>
        <div class="kia-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('staff.create') }}" class="btn btn-outline">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Staff') }}
            </a>
            <a href="{{ route('students.index') }}" class="btn btn-ghost">
                {{ __('View All Students') }}
            </a>
            <a href="{{ route('settings.index') }}" class="btn btn-ghost">
                {{ __('School Settings') }}
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">
                {{ __('Manage Users') }}
            </a>
        </div>
    </div>

    @include('dashboard.partials.arrivals-widget')

    {{-- Recent activity — same sensitive-field stripping as /audit --}}
    <div class="kia-card">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Recent Activity') }}</h2>
        </div>
        <div class="kia-card-body">
            @forelse($recentActivity as $log)
                @php
                    $attrs = $log->properties['attributes'] ?? [];
                    $safe  = array_diff_key($attrs, array_flip(['password', 'remember_token', 'api_token', 'token', 'secret']));
                    $changedFields = array_keys($safe);
                @endphp
                <a href="{{ route('audit.index', $log->causer_id ? ['causer_id' => $log->causer_id] : []) }}"
                   style="display:flex;justify-content:space-between;gap:1rem;padding:.6rem 0;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;">
                    <div>
                        <span class="pill pill-royal" style="font-size:.7rem;">{{ $log->log_name }}</span>
                        {{ class_basename($log->subject_type ?? '') }} {{ $log->description }}
                        @if(count($changedFields))
                        <span style="color:var(--muted);">({{ implode(', ', array_slice($changedFields, 0, 3)) }}{{ count($changedFields) > 3 ? '…' : '' }})</span>
                        @endif
                        <div style="color:var(--muted);font-size:.8rem;">{{ $log->causer?->name ?? __('admin_dashboard.system') }}</div>
                    </div>
                    <div style="white-space:nowrap;color:var(--muted);font-size:.8rem;">{{ $log->created_at->diffForHumans() }}</div>
                </a>
            @empty
                <div class="kia-empty">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3>{{ __('No recent activity') }}</h3>
                    <p>{{ __('Activity will appear here as you use the system.') }}</p>
                </div>
            @endforelse
            @if($recentActivity->isNotEmpty())
            <div style="margin-top:.75rem;text-align:right;">
                <a href="{{ route('audit.index') }}" class="btn btn-ghost btn-sm">{{ __('admin_dashboard.view_full_audit_log') }} →</a>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
