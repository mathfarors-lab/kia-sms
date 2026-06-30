<x-app-layout>
    <x-slot name="title">Finance Dashboard</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Finance Dashboard</h1></div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('finance.report') }}" class="btn btn-ghost">Reports</a>
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">All Invoices</a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="kia-stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:1.5rem">
        <div class="kia-card kia-stat-card">
            <div class="kia-stat-label">Collected This Month</div>
            <div class="kia-stat-value" style="color:var(--ok)">${{ number_format($collectedMonth, 2) }}</div>
        </div>
        <div class="kia-card kia-stat-card">
            <div class="kia-stat-label">Total Outstanding</div>
            <div class="kia-stat-value" style="color:var(--warn)">${{ number_format($outstanding, 2) }}</div>
        </div>
        <div class="kia-card kia-stat-card">
            <div class="kia-stat-label">Overdue Invoices</div>
            <div class="kia-stat-value" style="color:var(--bad)">{{ $overdueCount }}</div>
        </div>
    </div>

    {{-- Trend --}}
    <div class="kia-card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1rem;font-size:1rem">Collection Trend (Last 7 Months)</h3>
        <div style="display:flex;align-items:flex-end;gap:.75rem;height:120px">
            @php $maxVal = $trend->max('collected') ?: 1; @endphp
            @foreach($trend as $t)
                @php $pct = min(100, $t['collected'] / $maxVal * 100); @endphp
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px">
                    <div style="font-size:.7rem;color:var(--muted)">${{ number_format($t['collected'], 0) }}</div>
                    <div style="width:100%;background:var(--royal);border-radius:4px 4px 0 0;height:{{ max(4, $pct * 0.8) }}px;opacity:.85"></div>
                    <div style="font-size:.65rem;color:var(--muted);white-space:nowrap">{{ $t['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recent payments --}}
    <div class="kia-card">
        <h3 style="margin-bottom:1rem;font-size:1rem">Recent Payments</h3>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr><th>Invoice</th><th>Student</th><th>Amount</th><th>Method</th><th>Date</th><th>By</th></tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $p)
                    <tr>
                        <td><a href="{{ route('invoices.show', $p->invoice) }}" class="kia-link">{{ $p->invoice->number }}</a></td>
                        <td>{{ $p->invoice->student?->name_en ?? '—' }}</td>
                        <td>${{ number_format($p->amount, 2) }}</td>
                        <td><span class="badge badge-secondary">{{ strtoupper($p->method) }}</span></td>
                        <td>{{ $p->paid_at->format('d M Y') }}</td>
                        <td>{{ $p->receivedBy?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="kia-table-empty">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .kia-stat-card { text-align:center; padding:1.5rem; }
    .kia-stat-label { font-size:.85rem; color:var(--muted); margin-bottom:.5rem; }
    .kia-stat-value { font-size:2rem; font-weight:700; }
    </style>
</x-app-layout>
