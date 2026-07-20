<x-app-layout>
    <x-slot name="title">{{ __('documents.billing_statement') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.billing_statement') }}</h1>
            <p class="kia-page-sub">{{ $student->name_km ?: $student->name_en }} — {{ $student->student_code }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('billing-statement.pdf', $student) }}" class="btn btn-primary">{{ __('documents.download_pdf') }}</a>
            <a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    <div class="kia-stats" style="margin-bottom:20px;">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('documents.total_charged') }}</div>
            <div class="kia-stat-value">${{ number_format($totalCharged, 2) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('documents.total_paid') }}</div>
            <div class="kia-stat-value" style="color:var(--ok)">${{ number_format($totalPaid, 2) }}</div>
        </div>
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('documents.current_balance') }}</div>
            <div class="kia-stat-value" style="color:{{ (float) $balance > 0 ? 'var(--warn)' : 'var(--ok)' }};">${{ number_format($balance, 2) }}</div>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('documents.billing_history') }}</h2></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('documents.date') }}</th>
                        <th>{{ __('documents.description') }}</th>
                        <th>{{ __('documents.charge') }}</th>
                        <th>{{ __('documents.payment') }}</th>
                        <th>{{ __('documents.running_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $row)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td>{{ $row['description'] }}</td>
                        <td>{{ $row['type'] === 'charge' ? '$' . number_format($row['amount'], 2) : '—' }}</td>
                        <td>{{ $row['type'] === 'payment' ? '$' . number_format($row['amount'], 2) : '—' }}</td>
                        <td style="font-weight:600;">${{ number_format($row['running_balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="kia-table-empty">{{ __('documents.no_billing_history') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
