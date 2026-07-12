<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@font-face { font-family:'NotoKhmer'; src:url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype'); }
body { font-family:'NotoKhmer','DejaVu Sans',sans-serif; font-size:10px; color:#1b1f33; }
h1 { font-size:14px; color:#2B3A8F; text-align:center; margin-bottom:4px; }
p.sub { text-align:center; color:#5b6079; font-size:9px; margin-bottom:16px; }
table { width:100%; border-collapse:collapse; }
th { background:#2B3A8F; color:#fff; padding:5px 8px; text-align:left; font-size:9px; }
td { padding:4px 8px; border-bottom:1px solid #E4E7F4; font-size:9px; }
tr:nth-child(even) td { background:#F6F7FC; }
</style>
</head>
<body>
<h1>Khmer Intellectual Academy — Fee Collection</h1>
<p class="sub">{{ $year->name }} · Generated {{ now()->format('d M Y') }} · Total collected: ${{ number_format($totalCollected, 2) }}</p>
<table>
    <thead>
        <tr>
            <th>Student</th>
            @if($rows->first() && isset($rows->first()->branch_name))<th>Branch</th>@endif
            <th>Invoice</th><th>Invoice Total</th><th>Paid</th><th>Method</th><th>Date</th>
        </tr>
    </thead>
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
        <tr><td colspan="7" style="text-align:center;padding:12px;">No payments found.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
