<x-app-layout>
    <x-slot name="title">{{ __('Library Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Library Dashboard') }}</h1>
    </div>

    <div class="kia-stats" style="margin-bottom:20px;">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.total_books') }}</div>
            <div class="kia-stat-value">{{ number_format($stats['total_books']) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.currently_issued') }}</div>
            <div class="kia-stat-value">{{ number_format($stats['currently_issued']) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.overdue_books') }}</div>
            <div class="kia-stat-value" style="{{ $stats['overdue_count'] > 0 ? 'color:var(--danger)' : '' }}">{{ $stats['overdue_count'] }}</div>
            @if($stats['overdue_count'] > 0)
                <a href="{{ route('book-issues.overdue') }}" style="font-size:.8rem;">{{ __('staff_dashboard.view_overdue') }} →</a>
            @endif
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('books.index') }}" class="btn btn-primary">{{ __('Manage Books') }}</a>
            <a href="{{ route('book-issues.overdue') }}" class="btn btn-outline">{{ __('staff_dashboard.view_overdue') }}</a>
        </div>
    </div>
</x-app-layout>
