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

body {
    font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif;
    font-size: 11px;
    color: #1b1f33;
    line-height: 1.5;
}

.page { padding: 20px 28px; }

.header {
    text-align: center;
    border-bottom: 3px solid #2B3A8F;
    padding-bottom: 10px;
    margin-bottom: 14px;
}
.header h1 { font-size: 17px; color: #2B3A8F; }
.header p  { font-size: 10px; color: #5b6079; margin-top: 3px; }
.school-logo { font-size: 10px; color: #ECC531; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; }

.info-grid { display: table; width: 100%; border: 1px solid #E4E7F4; border-radius: 4px; margin-bottom: 14px; }
.info-row  { display: table-row; }
.info-cell { display: table-cell; padding: 5px 10px; width: 50%; border-bottom: 1px solid #E4E7F4; }
.info-cell:first-child { border-right: 1px solid #E4E7F4; }
.info-label { color: #5b6079; font-size: 9px; display: block; }
.info-value { font-weight: bold; font-size: 11px; }

.section-title {
    font-size: 11px;
    font-weight: bold;
    color: #2B3A8F;
    background: #f0f3ff;
    padding: 5px 8px;
    margin-bottom: 4px;
    border-left: 3px solid #2B3A8F;
}
.weight-tag { font-size: 9px; color: #5b6079; font-weight: normal; }

table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
thead tr { background: #2B3A8F; color: #fff; }
th { padding: 5px 8px; text-align: left; font-size: 10px; }
td { padding: 4px 8px; border-bottom: 1px solid #E4E7F4; font-size: 10px; }
tr:nth-child(even) { background: #F6F7FC; }

.grade-badge {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 4px;
    background: #2B3A8F;
    color: #fff;
    font-size: 9px;
    font-weight: bold;
}

.summary {
    display: table;
    width: 100%;
    border: 2px solid #2B3A8F;
    border-radius: 4px;
    margin-top: 10px;
}
.summary-cell {
    display: table-cell;
    text-align: center;
    padding: 8px 4px;
    border-right: 1px solid #E4E7F4;
    width: 20%;
}
.summary-cell:last-child { border-right: none; }
.summary-label { font-size: 9px; color: #5b6079; }
.summary-value { font-size: 16px; font-weight: bold; color: #2B3A8F; }
.result-pass { color: #1f9d6b; }
.result-fail { color: #d8573f; }

.missing-warn {
    margin-top: 8px;
    padding: 6px 8px;
    background: #fffbe6;
    border: 1px solid #f0c040;
    border-radius: 4px;
    color: #7a5c00;
    font-size: 9px;
}

.remark-box {
    margin-top: 8px;
    padding: 6px 8px;
    background: #f6f7fc;
    border: 1px solid #E4E7F4;
    border-radius: 4px;
    font-size: 10px;
}

.footer {
    margin-top: 30px;
    display: table;
    width: 100%;
    font-size: 9px;
    color: #5b6079;
}
.sig-cell {
    display: table-cell;
    text-align: center;
    width: 33%;
    padding-top: 24px;
    border-top: 1px solid #1b1f33;
}
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="school-logo">KIA</div>
        <h1>Khmer Intellectual Academy</h1>
        <p>
            @if($semester === null)
                Annual Report Card / របាយការណ៍ប្រចាំឆ្នាំ
            @else
                Semester {{ $semester }} Report Card / របាយការណ៍ប្រចាំ​ត្រីមាស {{ $semester }}
            @endif
            &mdash; {{ $academicYear->name }}
        </p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Student Name / ឈ្មោះ</span>
                <span class="info-value">{{ $student->name_en }}@if($student->name_km) — {{ $student->name_km }}@endif</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Student Code / លេខសម្គាល់</span>
                <span class="info-value">{{ $student->student_code }}</span>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Gender / យេនឌ័រ</span>
                <span class="info-value">{{ ucfirst($student->gender ?? '—') }}</span>
            </div>
            <div class="info-cell">
                <span class="info-label">Section / ថ្នាក់</span>
                <span class="info-value">
                    @if($termResult->section)
                        {{ $termResult->section->schoolClass->name ?? '—' }} / {{ $termResult->section->name }}
                    @else —
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Component exam marks --}}
    @foreach($exams as $exam)
    <div class="section-title">
        {{ $exam->name }}
        <span class="weight-tag">({{ __('term_results.weight') }}: {{ $exam->weight }})</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Subject / មុខវិជ្ជា</th>
                <th>Coef</th>
                <th>Score / ពិន្ទុ</th>
                <th>Grade / ថ្នាក់</th>
            </tr>
        </thead>
        <tbody>
            @forelse($exam->marks as $mark)
            <tr>
                <td>{{ $mark->subject->name_en }}@if($mark->subject->name_km) / {{ $mark->subject->name_km }}@endif</td>
                <td>{{ $mark->subject->coefficient }}</td>
                <td>{{ $mark->score }}</td>
                <td><span class="grade-badge">{{ $mark->grade ?? '—' }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:#d8573f;font-style:italic;">No marks recorded</td></tr>
            @endforelse
        </tbody>
    </table>
    @endforeach

    {{-- Consolidated summary --}}
    <div class="summary">
        <div class="summary-cell">
            <div class="summary-label">Total / សរុប</div>
            <div class="summary-value">{{ $termResult->total }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Average / មធ្យម</div>
            <div class="summary-value">{{ $termResult->average }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">GPA</div>
            <div class="summary-value">{{ $termResult->gpa }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Rank / លំដាប់</div>
            <div class="summary-value">#{{ $termResult->rank }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Result / លទ្ធផល</div>
            <div class="summary-value {{ $termResult->result === 'pass' ? 'result-pass' : 'result-fail' }}">
                {{ strtoupper($termResult->result) }}
            </div>
        </div>
    </div>

    @if($termResult->has_missing_marks)
    <div class="missing-warn">⚠ Some exam marks were missing and were handled per the school's policy.</div>
    @endif

    @if($termResult->teacher_remark)
    <div class="remark-box">
        <strong>Class Teacher Remark / យោបល់គ្រូ:</strong> {{ $termResult->teacher_remark }}
    </div>
    @endif

    <div class="footer">
        <div class="sig-cell">Class Teacher / គ្រូថ្នាក់</div>
        <div class="sig-cell">Principal / នាយក</div>
        <div class="sig-cell">School Seal / ត្រាសាលា</div>
    </div>

</div>
</body>
</html>
