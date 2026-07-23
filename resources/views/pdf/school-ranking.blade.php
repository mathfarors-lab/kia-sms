<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1a1a1a; }

    .header { text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid #2563eb; }
    .header h1 { font-size: 15pt; font-weight: 700; color: #1e3a8a; }
    .header p  { font-size: 9pt; color: #64748b; margin-top: 4px; }

    .stats { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .stat  { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;
             padding: 6px 12px; text-align: center; flex: 1; min-width: 80px; }
    .stat-val   { font-size: 12pt; font-weight: 700; color: #1e3a8a; }
    .stat-label { font-size: 7pt; color: #64748b; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #1e3a8a; color: #fff;
        padding: 5px 6px; font-size: 8pt; font-weight: 600;
        text-align: left; border: 1px solid #1e3a8a;
    }
    tbody tr:nth-child(even) td { background: #f8fafc; }
    tbody tr:nth-child(-n+3) td { background: #fef9c3; }
    tbody td { padding: 4px 6px; border: 1px solid #e2e8f0; font-size: 8.5pt; vertical-align: middle; }
    .rank-cell { text-align: center; font-weight: 700; width: 44px; }
    .num-cell  { text-align: right; font-variant-numeric: tabular-nums; }
    .center    { text-align: center; }
    .pass  { color: #15803d; font-weight: 600; }
    .fail  { color: #dc2626; font-weight: 600; }
    .muted { color: #64748b; font-size: 7.5pt; }

    /* Per-grade section headers */
    .grade-header td {
        background: #1e40af; color: #fff; font-weight: 700;
        font-size: 9pt; padding: 5px 8px;
    }

    .footer { margin-top: 12px; font-size: 7pt; color: #94a3b8; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $title }}</h1>
    <p>Generated {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; {{ $stats['total'] }} students ranked</p>
</div>

<div class="stats">
    <div class="stat">
        <div class="stat-val">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Students</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:#15803d;">{{ $stats['pass'] }}</div>
        <div class="stat-label">Passed</div>
    </div>
    <div class="stat">
        <div class="stat-val" style="color:#dc2626;">{{ $stats['fail'] }}</div>
        <div class="stat-label">Failed</div>
    </div>
    <div class="stat">
        <div class="stat-val">{{ $stats['pass_rate'] }}%</div>
        <div class="stat-label">Pass Rate</div>
    </div>
    <div class="stat">
        <div class="stat-val">{{ $stats['average'] }}</div>
        <div class="stat-label">School Average</div>
    </div>
    @if($stats['top'])
    <div class="stat" style="flex:2;">
        <div class="stat-val" style="font-size:10pt;">{{ $stats['top']->name_km ?: $stats['top']->name_en }}</div>
        <div class="stat-label">Top Student — {{ $stats['top']->average }}% ({{ $stats['top']->class_name }})</div>
    </div>
    @endif
</div>

{{-- School-wide table --}}
<table>
    <thead>
        <tr>
            <th class="rank-cell">School Rank</th>
            <th class="rank-cell">Grade Rank</th>
            <th>Student Name</th>
            <th>Grade</th>
            <th>Section</th>
            <th>Roll No.</th>
            <th class="num-cell" style="width:60px;">Total</th>
            <th class="num-cell" style="width:60px;">Avg %</th>
            <th class="num-cell" style="width:40px;">GPA</th>
            <th class="center" style="width:44px;">Result</th>
        </tr>
    </thead>
    <tbody>
        @php $currentClass = null; @endphp
        @foreach($ranking as $row)
        @if($row->class_name !== $currentClass)
            @php $currentClass = $row->class_name; @endphp
            <tr class="grade-header">
                <td colspan="10">{{ $row->class_name ?? 'Unassigned' }}</td>
            </tr>
        @endif
        <tr>
            <td class="rank-cell">
                @if($row->school_rank <= 3)★ @endif#{{ $row->school_rank }}
            </td>
            <td class="rank-cell" style="color:#64748b;">#{{ $row->class_rank }}</td>
            <td>
                <strong>{{ $row->name_km ?: $row->name_en }}</strong>
                @if($row->name_km)
                <br><span class="muted">{{ $row->name_en }}</span>
                @endif
            </td>
            <td>{{ $row->class_name ?? '—' }}</td>
            <td>{{ $row->section_name ?? '—' }}</td>
            <td class="center muted">{{ $row->roll_no ?? '—' }}</td>
            <td class="num-cell">{{ number_format($row->total, 1) }}</td>
            <td class="num-cell"><strong>{{ number_format($row->average, 1) }}</strong></td>
            <td class="num-cell">{{ number_format($row->gpa, 2) }}</td>
            <td class="center {{ $row->result === 'pass' ? 'pass' : 'fail' }}">{{ strtoupper($row->result) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">KIA School System &mdash; Confidential &mdash; {{ now()->format('Y') }}</div>
</body>
</html>
