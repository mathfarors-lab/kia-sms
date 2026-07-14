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
<h1>Khmer Intellectual Academy — Staff Punctuality</h1>
<p class="sub">{{ $month }} · Generated {{ now()->format('d M Y') }}</p>
<table>
    <thead><tr><th>Staff</th><th>Code</th><th>On Time</th><th>Late</th><th>Days Recorded</th></tr></thead>
    <tbody>
        @forelse($rows as $row)
        <tr>
            <td>{{ $row->name }}</td>
            <td>{{ $row->staff_code }}</td>
            <td>{{ $row->on_time }}</td>
            <td>{{ $row->late }}</td>
            <td>{{ $row->total_days }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:12px;">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
