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
<h1>Khmer Intellectual Academy — Enrollment Roster</h1>
<p class="sub">{{ $year->name }} · Generated {{ now()->format('d M Y') }} · {{ $students->count() }} students</p>
<table>
    <thead>
        <tr>
            <th>#</th><th>Code</th><th>Name (EN)</th><th>Name (KM)</th><th>Gender</th>
            @if($students->first() && isset($students->first()->branch_name))<th>Branch</th>@endif
            <th>Class</th><th>Section</th><th>Roll</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $i => $s)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $s->student_code }}</td>
            <td>{{ $s->name_en }}</td>
            <td>{{ $s->name_km }}</td>
            <td>{{ ucfirst($s->gender ?? '') }}</td>
            @if(isset($s->branch_name))<td>{{ $s->branch_name }}</td>@endif
            <td>{{ $s->class_name }}</td>
            <td>{{ $s->section_name }}</td>
            <td>{{ $s->roll_no ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
