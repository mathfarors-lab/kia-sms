<x-app-layout>
    <x-slot name="title">Record Payment</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Record Payment</h1>
            <p class="kia-page-sub">{{ $invoice->number }} — {{ $invoice->student->name_en }}</p>
        </div>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:480px">
        <div style="background:var(--paper);border-radius:6px;padding:.75rem 1rem;margin-bottom:1.5rem">
            <div style="display:flex;justify-content:space-between">
                <span style="color:var(--muted)">Invoice Total</span>
                <strong>${{ number_format($invoice->total, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between">
                <span style="color:var(--muted)">Already Paid</span>
                <strong style="color:var(--ok)">${{ number_format($invoice->paid, 2) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--line);margin-top:.5rem;padding-top:.5rem">
                <span style="font-weight:600">Balance Due</span>
                <strong style="color:var(--bad)">${{ number_format($invoice->remainingBalance(), 2) }}</strong>
            </div>
        </div>

        @if($errors->any())
            <div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('payments.store', $invoice) }}" class="kia-form">
            @csrf
            <div class="kia-form-group">
                <label class="kia-label">Amount (USD) *</label>
                <input name="amount" type="number" step="0.01" min="0.01"
                       max="{{ $invoice->remainingBalance() }}"
                       value="{{ old('amount', $invoice->remainingBalance()) }}"
                       class="kia-input @error('amount') is-invalid @enderror">
                @error('amount')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Payment Method *</label>
                <select name="method" class="kia-select @error('method') is-invalid @enderror">
                    @foreach(['cash','bank','khqr','aba','wing'] as $m)
                        <option value="{{ $m }}" @selected(old('method') === $m)>{{ strtoupper($m) }}</option>
                    @endforeach
                </select>
                @error('method')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Reference / Transaction ID</label>
                <input name="reference" value="{{ old('reference') }}" class="kia-input" placeholder="Optional">
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Confirm Payment</button>
                <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
