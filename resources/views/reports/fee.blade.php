<x-app-layout>
    <x-slot name="title">Fee Collection</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Fee Collection</h1>
            <p class="kia-page-sub">{{ $year->name }} · Total collected: ${{ number_format($totalCollected, 2) }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('reports.fees', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-secondary">⬇ PDF</a>
            <a href="{{ route('reports.fees', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-secondary">⬇ CSV</a>
            <a href="{{ route('reports.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>Student</th>
                    @if($rows->first() && isset($rows->first()->branch_name))<th>Branch</th>@endif
                    <th>Invoice</th><th>Invoice Total</th><th>Paid</th><th>Method</th><th>Date</th>
                </tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->name_en }}</td>
                        @if(isset($row->branch_name))<td>{{ $row->branch_name }}</td>@endif
                        <td>{{ $row->invoice_number }}</td>
                        <td>${{ number_format($row->total_amount, 2) }}</td>
                        <td>${{ number_format($row->payment_amount, 2) }}</td>
                        <td>{{ ucfirst($row->method) }}</td>
                        <td>{{ $row->paid_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">No payments found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
