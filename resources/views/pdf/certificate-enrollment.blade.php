<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8">
<style>
@font-face { font-family: 'NotoKhmer'; font-style: normal; font-weight: normal; src: url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype'); }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif; font-size: 12px; color: #1b1f33; line-height: 1.7; }
.page { padding: 40px 50px; }
.header { text-align: center; border-bottom: 2px solid #2B3A8F; padding-bottom: 16px; margin-bottom: 24px; }
.logo { font-size: 11px; color: #ECC531; font-weight: bold; letter-spacing: 2px; }
.title { font-size: 20px; font-weight: bold; color: #2B3A8F; margin: 8px 0 4px; }
.subtitle { font-size: 13px; color: #5b6079; }
.cert-no { font-size: 10px; color: #5b6079; margin-top: 8px; }
.body { margin: 20px 0; font-size: 12px; line-height: 2; }
.highlight { font-weight: bold; color: #2B3A8F; }
.seal-block { margin-top: 50px; display: table; width: 100%; font-size: 10px; color: #5b6079; }
.sig { display: table-cell; text-align: center; width: 33%; padding-top: 28px; border-top: 1px solid #1b1f33; }
.date { text-align: right; font-size: 11px; color: #5b6079; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="logo">KIA</div>
        <div class="title">Enrollment Confirmation Letter</div>
        <div class="subtitle">លិខិតបញ្ជាក់ការចុះឈ្មោះ</div>
        <div class="cert-no">Certificate No: {{ $certNo }}</div>
    </div>

    <div class="date">Date / កាលបរិច្ឆេទ: {{ now()->format('d F Y') }}</div>

    <div class="body">
        <p>To Whom It May Concern,</p>
        <br>
        <p>This is to certify that <span class="highlight">{{ $student->name_en }}@if($student->name_km) ({{ $student->name_km }})@endif</span>,
        bearing Student ID <span class="highlight">{{ $student->student_code }}</span>,
        is currently enrolled as a student at <b>Khmer Intellectual Academy (KIA)</b>
        for the academic year <span class="highlight">{{ $year?->name ?? now()->year }}</span>.</p>
        <br>
        <p>This letter is issued upon request for official purposes.</p>
        <br>
        <p>យើងខ្ញុំសូមបញ្ជាក់ថា
        <span class="highlight">{{ $student->name_en }}@if($student->name_km) ({{ $student->name_km }})@endif</span>
        លេខសម្គាល់ <span class="highlight">{{ $student->student_code }}</span>
        បានចុះឈ្មោះជាសិស្សសិក្សានៅ <b>Khmer Intellectual Academy (KIA)</b>
        ក្នុងឆ្នាំសិក្សា <span class="highlight">{{ $year?->name ?? now()->year }}</span>។</p>
    </div>

    <div class="seal-block">
        <div class="sig">Registrar / ការិយាល័យ</div>
        <div class="sig">Principal / នាយក</div>
        <div class="sig">Official Seal / ត្រាផ្លូវការ</div>
    </div>
</div>
</body>
</html>
