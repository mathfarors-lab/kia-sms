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
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif; font-size: 11px; color: #1b1f33; line-height: 1.5; }
.page { padding: 24px 32px; }
.header { text-align: center; border-bottom: 3px solid #2B3A8F; padding-bottom: 12px; margin-bottom: 16px; }
.header h1 { font-size: 18px; color: #2B3A8F; }
.header p { font-size: 10px; color: #5b6079; margin-top: 3px; }
.school-logo { font-size: 10px; color: #ECC531; font-weight: bold; letter-spacing: 2px; }
.student-info { display: table; width: 100%; border: 1px solid #E4E7F4; border-radius: 4px; margin-bottom: 16px; }
.si-row { display: table-row; }
.si-cell { display: table-cell; padding: 5px 10px; width: 33%; border-bottom: 1px solid #E4E7F4; border-right: 1px solid #E4E7F4; }
.si-cell:last-child { border-right: none; }
.si-label { font-size: 9px; color: #5b6079; display: block; }
.si-value { font-size: 11px; font-weight: bold; }
.year-block { margin-bottom: 16px; }
.year-title { font-size: 13px; font-weight: bold; color: #2B3A8F; background: #f0f3ff; padding: 6px 10px; border-left: 3px solid #2B3A8F; margin-bottom: 8px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10px; }
thead tr { background: #2B3A8F; color: #fff; }
th { padding: 5px 8px; text-align: left; }
td { padding: 4px 8px; border-bottom: 1px solid #E4E7F4; }
tr:nth-child(even) { background: #F6F7FC; }
.summary-row { display: table; width: 100%; border: 1px solid #2B3A8F; border-radius: 4px; }
.sum-cell { display: table-cell; text-align: center; padding: 6px; border-right: 1px solid #E4E7F4; font-size: 10px; }
.sum-cell:last-child { border-right: none; }
.sum-label { color: #5b6079; font-size: 8px; }
.sum-value { font-size: 14px; font-weight: bold; color: #2B3A8F; }
.pass { color: #1f9d6b; } .fail { color: #d8573f; }
.footer { margin-top: 40px; display: table; width: 100%; font-size: 9px; color: #5b6079; }
.sig-cell { display: table-cell; text-align: center; width: 33%; padding-top: 24px; border-top: 1px solid #1b1f33; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="school-logo">KIA</div>
        <h1>Khmer Intellectual Academy</h1>
        <p>Official Academic Transcript / ប្រវត្តិសិក្សាផ្លូវការ</p>
        <p>{{ now()->format('d F Y') }}</p>
    </div>

    <div class="student-info">
        <div class="si-row">
            <div class="si-cell">
                <span class="si-label">Name (EN) / ឈ្មោះ</span>
                <span class="si-value">{{ $student->name_en }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Name (KM) / ឈ្មោះខ្មែរ</span>
                <span class="si-value">{{ $student->name_km ?? '—' }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Student ID / លេខ</span>
                <span class="si-value">{{ $student->student_code }}</span>
            </div>
        </div>
        <div class="si-row">
            <div class="si-cell">
                <span class="si-label">Gender / យេនឌ័រ</span>
                <span class="si-value">{{ ucfirst($student->gender ?? '—') }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Status / ស្ថានភាព</span>
                <span class="si-value">{{ ucfirst($student->status ?? '—') }}</span>
            </div>
            <div class="si-cell">
                <span class="si-label">Date of Birth / ថ្ងៃខែឆ្នាំកំណើត</span>
                <span class="si-value">{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</span>
            </div>
        </div>
    </div>

    @forelse($yearBlocks as $block)
    <div class="year-block">
        <div class="year-title">{{ $block['year']->name }}</div>

        @if($block['allSubjectIds']->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Subject / មុខវិជ្ជា</th>
                    @if(isset($block['subjectsBySem'][1]))<th>S1 Avg</th><th>S1</th>@endif
                    @if(isset($block['subjectsBySem'][2]))<th>S2 Avg</th><th>S2</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($block['allSubjectIds'] as $subjectId)
                @php
                    $s1 = $block['subjectsBySem'][1][$subjectId] ?? null;
                    $s2 = $block['subjectsBySem'][2][$subjectId] ?? null;
                    $subject = $s1['subject'] ?? $s2['subject'] ?? null;
                @endphp
                @if($subject)
                <tr>
                    <td>{{ $subject->name_en }}@if($subject->name_km) / {{ $subject->name_km }}@endif</td>
                    @if(isset($block['subjectsBySem'][1]))<td>{{ $s1['average'] ?? '—' }}</td><td>{{ $s1['grade'] ?? '—' }}</td>@endif
                    @if(isset($block['subjectsBySem'][2]))<td>{{ $s2['average'] ?? '—' }}</td><td>{{ $s2['grade'] ?? '—' }}</td>@endif
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="summary-row">
            @foreach([1 => 'Semester 1', 2 => 'Semester 2', 'annual' => 'Annual'] as $key => $label)
            @if($tr = $block['termResults']->get($key))
            <div class="sum-cell">
                <div class="sum-label">{{ $label }}</div>
                <div class="sum-value">{{ $tr->average }}</div>
                <div class="sum-label">GPA {{ $tr->gpa }} &bull; Rank #{{ $tr->rank }}</div>
                <div class="{{ $tr->result === 'pass' ? 'pass' : 'fail' }}" style="font-size:9px;font-weight:bold;">{{ strtoupper($tr->result) }}</div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
    @empty
    <p style="text-align:center;color:#5b6079;margin:20px 0;">No published academic results on record.</p>
    @endforelse

    <div class="footer">
        <div class="sig-cell">Registrar / ការិយាល័យ</div>
        <div class="sig-cell">Principal / នាយក</div>
        <div class="sig-cell">School Seal / ត្រាសាលា</div>
    </div>
</div>
</body>
</html>
