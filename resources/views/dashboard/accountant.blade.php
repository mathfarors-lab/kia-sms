<x-app-layout>
    <x-slot name="title">{{ __('Finance Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Finance Dashboard') }}</h1>
    </div>
    <div class="kia-stats">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Total Invoiced') }}</div>
            <div class="kia-stat-value">$0</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Collected') }}</div>
            <div class="kia-stat-value">$0</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('Outstanding') }}</div>
            <div class="kia-stat-value">$0</div>
        </div>
    </div>
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('Finance module coming in Phase 4') }}</h3>
            </div>
        </div>
    </div>
</x-app-layout>
