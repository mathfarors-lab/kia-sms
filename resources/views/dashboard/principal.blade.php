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
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Staff') }}</div>
            <div class="kia-stat-value">{{ $stats['total_staff'] }}</div>
        </div>
    </div>
    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Pending Admissions') }}</h2></div>
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('No pending admissions') }}</h3>
                <p>{{ __('Admissions module coming in Phase 2.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
