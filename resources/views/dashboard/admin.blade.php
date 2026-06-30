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
            <div class="kia-stat-value">$0</div>
            <span class="pill pill-warn">{{ __('Phase 4') }}</span>
        </div>

        <div class="kia-stat">
            <div class="kia-stat-icon warn">
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <polyline points="9,11 12,14 22,4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <div class="kia-stat-label">{{ __('Attendance Today') }}</div>
            <div class="kia-stat-value">—</div>
            <span class="pill pill-muted">{{ __('Phase 2') }}</span>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="kia-card" style="margin-bottom:24px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Quick Actions') }}</h2>
        </div>
        <div class="kia-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('students.create') }}" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                {{ __('Add Student') }}
            </a>
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
        </div>
    </div>

    {{-- Recent activity placeholder --}}
    <div class="kia-card">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Recent Activity') }}</h2>
        </div>
        <div class="kia-card-body">
            <div class="kia-empty">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h3>{{ __('No recent activity') }}</h3>
                <p>{{ __('Activity will appear here as you use the system.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
