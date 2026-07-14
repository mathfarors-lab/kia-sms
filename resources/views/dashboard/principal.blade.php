<x-app-layout>
    <x-slot name="title">{{ __('Principal Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Principal Dashboard') }}</h1>
            <p class="kia-page-sub">{{ __('Welcome,') }} {{ auth()->user()->name }}</p>
        </div>
    </div>
    <div class="kia-stats">
        <div class="kia-stat">
            <div class="kia-stat-icon royal"></div>
            <div class="kia-stat-label">{{ __('Total Students') }}</div>
            <div class="kia-stat-value">{{ $stats['total_students'] }}</div>
            <span class="pill pill-ok">{{ $stats['enrolled'] }} {{ __('enrolled') }}</span>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Staff') }}</div>
            <div class="kia-stat-value">{{ $stats['total_staff'] }}</div>
        </div>
    </div>

    @include('dashboard.partials.arrivals-widget')

    <div class="kia-card">
        <div class="kia-card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2 class="kia-card-title">
                {{ __('admissions.pending_admissions') }}
                @if($pendingAdmissionsCount > 0)
                <span class="pill pill-warn" style="margin-left:8px;">{{ $pendingAdmissionsCount }}</span>
                @endif
            </h2>
            <a href="{{ route('admissions.index') }}" class="btn btn-sm btn-outline">{{ __('admissions.view_all') }}</a>
        </div>
        <div class="kia-card-body">
            @forelse($pendingAdmissions as $app)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.55rem 0;border-bottom:1px solid var(--line);">
                <div>
                    <a href="{{ route('admissions.show', $app) }}" style="font-weight:600;">{{ $app->name_km ?: $app->name_en }}</a>
                    <span class="mono" style="font-size:.75rem;color:var(--muted);margin-left:6px;">{{ $app->application_no }}</span>
                </div>
                <x-admission-status-pill :status="$app->status" />
            </div>
            @empty
            <div class="kia-empty">
                <h3>{{ __('No pending admissions') }}</h3>
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
