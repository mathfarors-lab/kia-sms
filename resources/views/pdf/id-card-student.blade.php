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
    font-size: 8pt;
    color: #1b1f33;
    width: 242.55pt;
    height: 153.07pt;
    overflow: hidden;
}
.card { width: 100%; height: 100%; border-radius: 6pt; overflow: hidden; }
.header {
    background: #2B3A8F;
    color: #fff;
    padding: 4pt 8pt;
    display: table;
    width: 100%;
}
.header-logo { display: table-cell; font-size: 10pt; font-weight: bold; letter-spacing: 1pt; }
.header-school { display: table-cell; font-size: 7pt; padding-left: 6pt; vertical-align: middle; }
.header-year { display: table-cell; text-align: right; font-size: 7pt; color: #ECC531; vertical-align: middle; }
.body { display: table; width: 100%; padding: 6pt; }
.photo-cell { display: table-cell; width: 55pt; vertical-align: top; }
.photo-cell img, .photo-placeholder {
    width: 55pt;
    height: 70pt;
    object-fit: cover;
    border: 0.5pt solid #ddd;
    border-radius: 3pt;
}
.photo-placeholder {
    background: #e8eaf0;
    text-align: center;
    font-size: 7pt;
    color: #888;
    padding-top: 28pt;
    line-height: 1.2;
}
.info-cell { display: table-cell; padding-left: 6pt; vertical-align: top; }
.name-en { font-size: 10pt; font-weight: bold; color: #1b1f33; line-height: 1.2; }
.name-km { font-size: 9pt; color: #2B3A8F; line-height: 1.2; margin-top: 1pt; }
.meta { font-size: 7.5pt; color: #5b6079; margin-top: 4pt; line-height: 1.6; }
.qr-cell { display: table-cell; vertical-align: bottom; width: 50pt; text-align: right; }
.qr-cell img { width: 48pt; height: 48pt; }
.footer {
    background: #f0f3ff;
    text-align: center;
    font-size: 6.5pt;
    color: #5b6079;
    padding: 2pt;
    letter-spacing: 0.5pt;
}
</style>
</head>
<body>
<div class="card">
    <div class="header">
        <div class="header-logo">KIA</div>
        <div class="header-school">Khmer Intellectual Academy</div>
        <div class="header-year">{{ $year?->name }}</div>
    </div>
    <div class="body">
        <div class="photo-cell">
            @if($photoUri)
                <img src="{{ $photoUri }}" alt="photo">
            @else
                <div class="photo-placeholder">No<br>Photo</div>
            @endif
        </div>
        <div class="info-cell">
            <div class="name-en">{{ $student->name_en }}</div>
            @if($student->name_km)
            <div class="name-km">{{ $student->name_km }}</div>
            @endif
            <div class="meta">
                <b>ID:</b> {{ $student->student_code }}<br>
                @if($section)
                <b>Class:</b> {{ $section->schoolClass->name ?? '' }} / {{ $section->name }}<br>
                @endif
                <b>Gender:</b> {{ ucfirst($student->gender ?? '') }}
            </div>
        </div>
        <div class="qr-cell" data-qr-payload="{{ $student->student_code }}">
            <img src="{{ $qrUri }}" alt="QR">
        </div>
    </div>
    <div class="footer">STUDENT ID / អត្តសញ្ញាណប័ណ្ណ</div>
</div>
</body>
</html>
