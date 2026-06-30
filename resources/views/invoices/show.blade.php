<x-app-layout>
    <x-slot name="title">Invoice {{ $invoice->number }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $invoice->number }}</h1>
            @php $statusColors = ['paid'=>'success','partial'=>'warn','unpaid'=>'secondary','overdue'=>'danger']; @endphp
            <span class="badge badge-{{ $statusColors[$invoice->status] ?? 'secondary' }}" style="font-size:.9rem">{{ ucfirst($invoice->status) }}</span>
        </div>
        <div style="display:flex;gap:.5rem">
            @can('payments.record')
                @unless($invoice->isPaid())
                    <a href="{{ route('payments.create', $invoice) }}" class="btn btn-primary">Record Payment</a>
                @endunless
            @endcan
            <a href="{{ route('invoices.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
        <div>
            {{-- Invoice details --}}
            <div class="kia-card" style="margin-bottom:1.5rem">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:.5rem 0 1rem">
                    <div><span class="kia-label">Student</span><div>{{ $invoice->student->name_en }}</div></div>
                    <div><span class="kia-label">Year</span><div>{{ $invoice->academicYear->name }}</div></div>
                    <div><span class="kia-label">Term</span><div>{{ $invoice->term }}</div></div>
                    <div><span class="kia-label">Due Date</span><div>{{ $invoice->due_date?->format('d M Y') ?? '—' }}</div></div>
                </div>

                <table class="kia-table">
                    <thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td style="text-align:right">${{ number_format($item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:600;background:var(--paper)">
                            <td>Total</td>
                            <td style="text-align:right">${{ number_format($invoice->total, 2) }}</td>
                        </tr>
                        <tr style="color:var(--ok)">
                            <td>Paid</td>
                            <td style="text-align:right">${{ number_format($invoice->paid, 2) }}</td>
                        </tr>
                        <tr style="font-weight:700;color:{{ $invoice->isPaid() ? 'var(--ok)' : 'var(--bad)' }}">
                            <td>Balance Due</td>
                            <td style="text-align:right">${{ number_format($invoice->remainingBalance(), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Payment history --}}
            @if($invoice->payments->count())
            <div class="kia-card">
                <h3 style="margin-bottom:1rem;font-size:1rem">Payment History</h3>
                <div class="kia-table-wrap">
                    <table class="kia-table">
                        <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Received By</th><th></th></tr></thead>
                        <tbody>
                            @foreach($invoice->payments as $p)
                            <tr>
                                <td>{{ $p->paid_at->format('d M Y H:i') }}</td>
                                <td>${{ number_format($p->amount, 2) }}</td>
                                <td><span class="badge badge-secondary">{{ strtoupper($p->method) }}</span></td>
                                <td>{{ $p->reference ?? '—' }}</td>
                                <td>{{ $p->receivedBy?->name ?? '—' }}</td>
                                <td><a href="{{ route('payments.receipt', $p) }}" class="btn btn-sm btn-ghost">Receipt</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- KHQR Panel --}}
        @if($qrPayload && !$invoice->isPaid())
        <div class="kia-card" style="text-align:center">
            <h3 style="margin-bottom:.5rem;font-size:1rem">Scan to Pay (KHQR)</h3>
            <p style="font-size:.8rem;color:var(--muted);margin-bottom:1rem">Scan with any Bakong-compatible wallet</p>
            <div id="khqr-canvas" style="margin:0 auto;width:200px;height:200px"></div>
            <p style="font-size:.7rem;color:var(--muted);margin-top:.75rem">
                Payment confirmation requires manual verification by the accountant.<br>
                <em>Bakong webhook integration: TODO</em>
            </p>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            new QRCode(document.getElementById('khqr-canvas'), {
                text: {{ Js::from($qrPayload) }},
                width: 200, height: 200,
                colorDark: '#2B3A8F', colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        });
        </script>
        @endif
    </div>
</x-app-layout>
