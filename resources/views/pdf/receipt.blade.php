<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8">
<style>
@font-face {
    font-family: 'NotoKhmer';
    font-style: normal;
    font-weight: normal;
    src: url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype');
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'NotoKhmer','DejaVu Sans',sans-serif; font-size:12px; color:#1b1f33; }
.page { padding:20px 28px; }
.header { text-align:center; border-bottom:2px solid #2B3A8F; padding-bottom:10px; margin-bottom:14px; }
.header h1 { font-size:16px; color:#2B3A8F; }
.header p { font-size:10px; color:#5b6079; margin-top:3px; }
.row { display:table; width:100%; margin-bottom:6px; }
.cell { display:table-cell; width:50%; }
.label { color:#5b6079; font-size:10px; }
.value { font-weight:600; font-size:12px; }
.divider { border-top:1px solid #E4E7F4; margin:12px 0; }
.amount-row { display:table; width:100%; }
.amount-label { display:table-cell; }
.amount-value { display:table-cell; text-align:right; }
.total-amount { font-size:18px; font-weight:700; color:#1f9d6b; }
.footer { margin-top:24px; border-top:1px dashed #E4E7F4; padding-top:10px; font-size:10px; color:#5b6079; text-align:center; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1>Khmer Intellectual Academy</h1>
        <p>Payment Receipt — វិក្កយបត្រទូទាត់</p>
    </div>

    <div class="row">
        <div class="cell">
            <div class="label">Receipt #</div>
            <div class="value">{{ $payment->id }}</div>
        </div>
        <div class="cell">
            <div class="label">Invoice # / លេខវិក្កយបត្រ</div>
            <div class="value">{{ $payment->invoice->number }}</div>
        </div>
    </div>
    <div class="row">
        <div class="cell">
            <div class="label">Student / សិស្ស</div>
            <div class="value">{{ $payment->invoice->student->name_en }}</div>
            <div class="value" style="font-size:11px">{{ $payment->invoice->student->name_km }}</div>
        </div>
        <div class="cell">
            <div class="label">Date / កាលបរិច្ឆេទ</div>
            <div class="value">{{ $payment->paid_at->format('d M Y H:i') }}</div>
        </div>
    </div>
    <div class="row">
        <div class="cell">
            <div class="label">Method / វិធីសាស្ត្រ</div>
            <div class="value">{{ strtoupper($payment->method) }}</div>
        </div>
        <div class="cell">
            <div class="label">Reference</div>
            <div class="value">{{ $payment->reference ?? '—' }}</div>
        </div>
    </div>

    <div class="divider"></div>

    <div class="amount-row">
        <div class="amount-label" style="font-size:13px;font-weight:600">Amount Paid / ចំនួនទូទាត់</div>
        <div class="amount-value total-amount">${{ number_format($payment->amount, 2) }}</div>
    </div>
    <div class="amount-row" style="margin-top:6px">
        <div class="amount-label" style="color:#5b6079">Remaining Balance / នៅខ្វះ</div>
        <div class="amount-value" style="color:#5b6079">${{ number_format($payment->invoice->remainingBalance(), 2) }}</div>
    </div>

    <div class="divider"></div>

    <div style="font-size:10px;color:#5b6079">
        Received by / ទទួលដោយ: {{ $payment->receivedBy?->name ?? '—' }}
    </div>

    <div class="footer">
        Thank you for your payment. / សូមអរគុណសម្រាប់ការទូទាត់របស់លោកអ្នក។
    </div>
</div>
</body>
</html>
