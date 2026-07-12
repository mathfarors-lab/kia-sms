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
<h1>Khmer Intellectual Academy — Attendance Summary</h1>
<p class="sub">{{ $year->name }} · Generated {{ now()->format('d M Y') }}</p>
<table>
    <thead>
        <tr>
            <th>Code</th><th>Student</th>
            @if($query->first() && isset($query->first()->branch_name))<th>Branch</th>@endif
            <th>Class</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>Rate</th>
        </tr>
    </thead>
    <tbody>
        @forelse($query as $row)
        <tr>
            <td>{{ $row->student_code }}</td>
            <td>{{ $row->name_en }}</td>
            @if(isset($row->branch_name))<td>{{ $row->branch_name }}</td>@endif
            <td>{{ $row->class_name }} / {{ $row->section_name }}</td>
            <td>{{ $row->total_days }}</td>
            <td>{{ $row->present }}</td>
            <td>{{ $row->absent }}</td>
            <td>{{ $row->late }}</td>
            <td>{{ $row->rate }}%</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:12px;">No data.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
