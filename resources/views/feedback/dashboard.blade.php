<x-app-layout>
    <x-slot name="title">{{ __('feedback.dashboard_title') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('feedback.dashboard_title') }}</h1></div>
        <a href="{{ route('feedback.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-stats" style="margin-bottom:1.5rem">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('feedback.total_open') }}</div>
            <div class="kia-stat-value">{{ $totalOpen }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('feedback.total_resolved') }}</div>
            <div class="kia-stat-value" style="color:var(--ok)">{{ $totalResolved }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('feedback.avg_resolution_hours') }}</div>
            <div class="kia-stat-value">{{ $avgResolutionHours !== null ? number_format($avgResolutionHours, 1) : '—' }}</div>
        </div>
    </div>

    <div class="kia-card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1rem;font-size:1rem">{{ __('feedback.by_category') }}</h3>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('feedback.category') }}</th>
                        <th>{{ __('feedback.status_open') }}</th>
                        <th>{{ __('feedback.status_in_progress') }}</th>
                        <th>{{ __('feedback.status_resolved') }}</th>
                        <th>{{ __('feedback.status_closed') }}</th>
                        <th>{{ __('feedback.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byCategory as $row)
                    <tr>
                        <td>{{ __('feedback.category_' . $row->category) }}</td>
                        <td>{{ $row->open }}</td>
                        <td>{{ $row->in_progress }}</td>
                        <td>{{ $row->resolved }}</td>
                        <td>{{ $row->closed }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="kia-table-empty">{{ __('feedback.no_items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="kia-card">
        <h3 style="margin-bottom:1rem;font-size:1rem">{{ __('feedback.volume_by_month') }}</h3>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr><th>{{ __('feedback.month') }}</th><th>{{ __('feedback.total') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($volumeByMonth as $row)
                    <tr><td>{{ $row->month }}</td><td>{{ $row->total }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="kia-table-empty">{{ __('feedback.no_items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
