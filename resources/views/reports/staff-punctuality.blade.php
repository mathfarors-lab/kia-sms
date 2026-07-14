<x-app-layout>
    <x-slot name="title">{{ __('gate.staff_punctuality') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('gate.staff_punctuality') }}</h1>
            <p class="kia-page-sub">{{ $month }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('reports.staff-punctuality', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary">⬇ PDF</a>
            <a href="{{ route('reports.staff-punctuality', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-secondary">⬇ CSV</a>
            <a href="{{ route('reports.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    <div class="kia-card" style="margin-bottom:16px;max-width:260px;">
        <div class="kia-card-body">
            <form method="GET">
                <label class="form-label">{{ __('Month') }}</label>
                <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>{{ __('gate.col_staff') }}</th><th>{{ __('Code') }}</th>
                    <th>{{ __('gate.col_on_time') }}</th><th>{{ __('gate.col_late') }}</th><th>{{ __('gate.col_total_days') }}</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="mono">{{ $row->staff_code }}</td>
                        <td>{{ $row->on_time }}</td>
                        <td>{{ $row->late }}</td>
                        <td>{{ $row->total_days }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--text-muted)">{{ __('No data.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
