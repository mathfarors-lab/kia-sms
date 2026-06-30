<x-app-layout>
    <x-slot name="title">Receipt #{{ $payment->id }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Payment Receipt</h1>
            <p class="kia-page-sub">{{ $payment->invoice->number }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('payments.receipt-pdf', $payment) }}" class="btn btn-primary" target="_blank">↓ PDF</a>
            <a href="{{ route('invoices.show', $payment->invoice) }}" class="btn btn-ghost">← Invoice</a>
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card" style="max-width:520px">
        <div style="text-align:center;padding:.75rem 0 1.25rem;border-bottom:2px solid var(--royal)">
            <strong style="color:var(--royal);font-size:1.1rem">Khmer Intellectual Academy</strong>
            <div style="color:var(--muted);font-size:.85rem;margin-top:.25rem">Payment Receipt</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:1rem 0;border-bottom:1px solid var(--line)">
            <div><span class="kia-label">Receipt #</span><div>{{ $payment->id }}</div></div>
            <div><span class="kia-label">Invoice #</span><div>{{ $payment->invoice->number }}</div></div>
            <div><span class="kia-label">Student</span><div>{{ $payment->invoice->student->name_en }}</div></div>
            <div><span class="kia-label">Date</span><div>{{ $payment->paid_at->format('d M Y H:i') }}</div></div>
            <div><span class="kia-label">Method</span><div>{{ strtoupper($payment->method) }}</div></div>
            <div><span class="kia-label">Reference</span><div>{{ $payment->reference ?? '—' }}</div></div>
        </div>

        <div style="padding:1rem 0;border-bottom:1px solid var(--line)">
            <div style="display:flex;justify-content:space-between">
                <span style="color:var(--muted)">Amount Paid</span>
                <strong style="font-size:1.25rem;color:var(--ok)">${{ number_format($payment->amount, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:.5rem">
                <span style="color:var(--muted)">Remaining Balance</span>
                <span>${{ number_format($payment->invoice->remainingBalance(), 2) }}</span>
            </div>
        </div>

        <div style="padding:.75rem 0;color:var(--muted);font-size:.8rem">
            Received by: {{ $payment->receivedBy?->name ?? '—' }}
        </div>
    </div>
</x-app-layout>
