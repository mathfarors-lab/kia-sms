<x-app-layout>
    <x-slot name="title">Invoices</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Invoices</h1>
            <p class="kia-page-sub">{{ $invoices->total() }} total</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('invoices.export-excel', request()->query()) }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('invoices.export-pdf', request()->query()) }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            @if(auth()->user()->hasRole('student') && auth()->user()->student)
            <a href="{{ route('billing-statement.show', auth()->user()->student) }}" class="btn btn-ghost">{{ __('documents.billing_statement') }}</a>
            @endif
            @can('invoices.create')
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">Generate Invoices</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    {{-- Filters (admin/accountant only) --}}
    @canany(['invoices.manage'])
    <div class="kia-card" style="padding:1rem 1.5rem;margin-bottom:1rem">
        <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
            <input name="search" value="{{ request('search') }}" placeholder="Invoice #" class="kia-input" style="width:160px">
            <select name="status" class="kia-select" style="width:150px">
                <option value="">All statuses</option>
                @foreach(['unpaid','partial','paid','overdue'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-ghost">Filter</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Clear</a>
        </form>
    </div>
    @endcanany

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Student</th>
                        <th>Term</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Due</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        @php
                            $statusColors = ['paid'=>'success','partial'=>'warn','unpaid'=>'secondary','overdue'=>'danger'];
                            $color = $statusColors[$inv->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td><a href="{{ route('invoices.show', $inv) }}" class="kia-link">{{ $inv->number }}</a></td>
                            <td>{{ $inv->student->name_en ?? '—' }}</td>
                            <td>{{ $inv->term }}</td>
                            <td>${{ number_format($inv->total, 2) }}</td>
                            <td>${{ number_format($inv->paid, 2) }}</td>
                            <td>${{ number_format($inv->remainingBalance(), 2) }}</td>
                            <td><span class="badge badge-{{ $color }}">{{ ucfirst($inv->status) }}</span></td>
                            <td>{{ $inv->due_date?->format('d M Y') ?? '—' }}</td>
                            <td class="kia-table-actions">
                                <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-ghost">View</a>
                                @can('payments.record')
                                    @unless($inv->isPaid())
                                        <a href="{{ route('payments.create', $inv) }}" class="btn btn-sm btn-primary">Pay</a>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="kia-table-empty">No invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kia-pagination">{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
