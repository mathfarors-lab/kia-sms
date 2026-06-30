<x-app-layout>
    <x-slot name="title">{{ __('Reception Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Reception Dashboard') }}</h1>
    </div>
    <div class="kia-stats">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Total Students') }}</div>
            <div class="kia-stat-value">{{ $stats['total_students'] }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Enrolled') }}</div>
            <div class="kia-stat-value">{{ $stats['enrolled'] }}</div>
        </div>
    </div>
    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Quick Actions') }}</h2></div>
        <div class="kia-card-body" style="display:flex;gap:12px;">
            <a href="{{ route('students.create') }}" class="btn btn-primary">{{ __('Register Student') }}</a>
            <a href="{{ route('students.index') }}" class="btn btn-outline">{{ __('Search Students') }}</a>
        </div>
    </div>
</x-app-layout>
