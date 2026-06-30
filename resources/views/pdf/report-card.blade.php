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
    font-size: 12px;
    color: #1b1f33;
    line-height: 1.5;
}

.page { padding: 24px 32px; }

.header {
    text-align: center;
    border-bottom: 3px solid #2B3A8F;
    padding-bottom: 12px;
    margin-bottom: 16px;
}
.header h1 { font-size: 18px; color: #2B3A8F; }
.header p { font-size: 11px; color: #5b6079; margin-top: 4px; }

.school-logo {
    font-size: 10px;
    color: #ECC531;
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.student-info {
    display: table;
    width: 100%;
    margin-bottom: 16px;
    border: 1px solid #E4E7F4;
    border-radius: 4px;
}
.info-row { display: table-row; }
.info-cell {
    display: table-cell;
    padding: 6px 12px;
    width: 50%;
    border-bottom: 1px solid #E4E7F4;
}
.info-cell:first-child { border-right: 1px solid #E4E7F4; }
.info-label { color: #5b6079; font-size: 10px; display: block; }
.info-value { font-weight: bold; font-size: 12px; }

table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
thead tr { background: #2B3A8F; color: #fff; }
th { padding: 8px 10px; text-align: left; font-size: 11px; }
td { padding: 6px 10px; border-bottom: 1px solid #E4E7F4; font-size: 11px; }
tr:nth-child(even) { background: #F6F7FC; }

.grade-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    background: #2B3A8F;
    color: #fff;
    font-size: 10px;
    font-weight: bold;
}

.summary {
    display: table;
    width: 100%;
    border: 2px solid #2B3A8F;
    border-radius: 4px;
    margin-top: 8px;
}
.summary-cell {
    display: table-cell;
    text-align: center;
    padding: 10px;
    border-right: 1px solid #E4E7F4;
    width: 25%;
}
.summary-cell:last-child { border-right: none; }
.summary-label { font-size: 10px; color: #5b6079; }
.summary-value { font-size: 18px; font-weight: bold; color: #2B3A8F; }
.result-pass { color: #1f9d6b; }
.result-fail { color: #d8573f; }

.footer {
    margin-top: 40px;
    display: table;
    width: 100%;
    font-size: 10px;
    color: #5b6079;
}
.sig-cell {
    display: table-cell;
    text-align: center;
    width: 33%;
    padding-top: 32px;
    border-top: 1px solid #1b1f33;
}
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="school-logo">KIA</div>
        <h1>Khmer Intellectual Academy</h1>
        <p>{{ $exam->name }} &mdash; {{ $exam->academicYear->name ?? '' }}</p>
        <p>របាយការណ៍ ការប្រឡង / Examination Report Card</p>
    </div>

    <div class="student-info">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Student Name / ឈ្មោះ</span>
                <span class="info-value">{{ $student->name_en }} — {{ $student->name_km }}</span>
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
                <span class="info-label">Exam Type / ប្រភេទប្រឡង</span>
                <span class="info-value">{{ ucfirst($exam->type) }}</span>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Subject / មុខវិជ្ជា</th>
                <th>Full Mark</th>
                <th>Score / ពិន្ទុ</th>
                <th>Grade / ថ្នាក់</th>
            </tr>
        </thead>
        <tbody>
            @foreach($marks as $mark)
            <tr>
                <td>{{ $mark->subject->name_en }}<br><small style="color:#5b6079">{{ $mark->subject->name_km }}</small></td>
                <td>{{ $mark->subject->full_mark }}</td>
                <td>{{ $mark->score }}</td>
                <td><span class="grade-badge">{{ $mark->grade ?? '—' }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($result)
    <div class="summary">
        <div class="summary-cell">
            <div class="summary-label">Average / មធ្យម</div>
            <div class="summary-value">{{ $result->average }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">GPA</div>
            <div class="summary-value">{{ $result->gpa }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Rank / លំដាប់</div>
            <div class="summary-value">#{{ $result->rank }}</div>
        </div>
        <div class="summary-cell">
            <div class="summary-label">Result / លទ្ធផល</div>
            <div class="summary-value {{ $result->result === 'pass' ? 'result-pass' : 'result-fail' }}">
                {{ strtoupper($result->result) }}
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <div class="sig-cell">Class Teacher<br>គ្រូថ្នាក់</div>
        <div class="sig-cell">Principal<br>នាយក</div>
        <div class="sig-cell">School Seal<br>ត្រាសាលា</div>
    </div>

</div>
</body>
</html>
