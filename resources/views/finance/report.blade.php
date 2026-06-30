<x-app-layout>
    <x-slot name="title">Finance Report</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Finance Report</h1></div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('finance.export-excel') }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('finance.export-pdf') }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            <a href="{{ route('finance.dashboard') }}" class="btn btn-ghost">← Dashboard</a>
        </div>
    </div>

    <div class="kia-card" style="margin-bottom:1.5rem">
        <h3 style="margin-bottom:1rem;font-size:1rem">Collection by Class</h3>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr><th>Class</th><th>Collected</th><th>Outstanding</th></tr>
                </thead>
                <tbody>
                    @forelse($byClass as $row)
                    <tr>
                        <td>{{ $row['class'] }}</td>
                        <td style="color:var(--ok)">${{ number_format($row['collected'], 2) }}</td>
                        <td style="color:var(--warn)">${{ number_format($row['outstanding'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="kia-table-empty">No data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="kia-card">
        <h3 style="margin-bottom:1rem;font-size:1rem">Monthly Breakdown (Last 6 Months)</h3>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr><th>Month</th><th>Invoiced</th><th>Collected</th><th>Collection Rate</th></tr>
                </thead>
                <tbody>
                    @foreach($byMonth as $row)
                    @php
                        $rate = $row['invoiced'] > 0 ? round($row['collected'] / $row['invoiced'] * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $row['month'] }}</td>
                        <td>${{ number_format($row['invoiced'], 2) }}</td>
                        <td style="color:var(--ok)">${{ number_format($row['collected'], 2) }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <div style="flex:1;background:var(--line);border-radius:4px;height:6px">
                                    <div style="background:var(--royal);border-radius:4px;height:6px;width:{{ $rate }}%"></div>
                                </div>
                                <span>{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
