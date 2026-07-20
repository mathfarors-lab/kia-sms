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
<h1>Khmer Intellectual Academy — Admissions</h1>
<p class="sub">Generated {{ now()->format('d M Y') }}</p>
<table>
    <thead>
        <tr>
            <th>Application #</th><th>Name (EN)</th><th>Name (KM)</th><th>Desired Class</th><th>Guardian Phone</th><th>Status</th><th>Submitted</th>
        </tr>
    </thead>
    <tbody>
        @foreach($applications as $app)
        <tr>
            <td>{{ $app->application_no }}</td>
            <td>{{ $app->name_en }}</td>
            <td>{{ $app->name_km }}</td>
            <td>{{ $app->desiredClass->name ?? '' }}</td>
            <td>{{ $app->guardian_phone ?? '' }}</td>
            <td>{{ ucfirst($app->status) }}</td>
            <td>{{ $app->created_at->format('d M Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
