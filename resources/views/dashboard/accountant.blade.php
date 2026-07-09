<x-app-layout>
    <x-slot name="title">{{ __('Finance Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Finance Dashboard') }}</h1>
    </div>

    <div class="kia-stats" style="margin-bottom:20px;">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.collected_this_month') }}</div>
            <div class="kia-stat-value">${{ number_format($stats['collected_month'], 2) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.outstanding') }}</div>
            <div class="kia-stat-value">${{ number_format($stats['outstanding'], 2) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('staff_dashboard.overdue_invoices') }}</div>
            <div class="kia-stat-value" style="{{ $stats['overdue_count'] > 0 ? 'color:var(--danger)' : '' }}">{{ $stats['overdue_count'] }}</div>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('invoices.index') }}" class="btn btn-primary">{{ __('nav.invoices') }}</a>
            <a href="{{ route('fee-structures.index') }}" class="btn btn-outline">{{ __('nav.fee_structures') }}</a>
            <a href="{{ route('finance.dashboard') }}" class="btn btn-ghost">{{ __('nav.finance_dashboard') }}</a>
        </div>
    </div>
</x-app-layout>
