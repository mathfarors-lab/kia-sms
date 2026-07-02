<!DOCTYPE html>
<html lang="km">
<head>
<meta charset="UTF-8">
<style>
@font-face { font-family: 'NotoKhmer'; font-style: normal; font-weight: normal; src: url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype'); }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif; font-size: 12px; color: #1b1f33; line-height: 1.7; }
.page { padding: 40px 50px; border: 6px double #2B3A8F; min-height: 760px; }
.ornament { text-align: center; font-size: 22px; color: #ECC531; margin-bottom: 8px; letter-spacing: 6px; }
.header { text-align: center; margin-bottom: 24px; }
.logo { font-size: 12px; color: #ECC531; font-weight: bold; letter-spacing: 2px; }
.title { font-size: 24px; font-weight: bold; color: #2B3A8F; margin: 10px 0 4px; }
.subtitle { font-size: 14px; color: #5b6079; }
.cert-no { font-size: 10px; color: #5b6079; margin-top: 8px; }
.divider { border: none; border-top: 1px solid #E4E7F4; margin: 16px 0; }
.body { text-align: center; margin: 24px 0; font-size: 13px; line-height: 2.2; }
.graduate-name { font-size: 28px; font-weight: bold; color: #2B3A8F; display: block; margin: 12px 0; }
.graduate-name-km { font-size: 20px; color: #2B3A8F; display: block; margin-bottom: 12px; }
.highlight { font-weight: bold; }
.seal-block { margin-top: 60px; display: table; width: 100%; font-size: 10px; color: #5b6079; }
.sig { display: table-cell; text-align: center; width: 33%; padding-top: 28px; border-top: 1px solid #1b1f33; }
.date { text-align: center; font-size: 11px; color: #5b6079; margin-bottom: 16px; }
</style>
</head>
<body>
<div class="page">
    <div class="ornament">✦ ✦ ✦</div>
    <div class="header">
        <div class="logo">KHMER INTELLECTUAL ACADEMY</div>
        <div class="title">Graduation Certificate</div>
        <div class="subtitle">វិញ្ញាបនប័ត្របញ្ចប់ការសិក្សា</div>
        <div class="cert-no">Certificate No: {{ $certNo }}</div>
    </div>
    <hr class="divider">

    <div class="body">
        <p>This is to certify that</p>
        <span class="graduate-name">{{ $student->name_en }}</span>
        @if($student->name_km)
        <span class="graduate-name-km">{{ $student->name_km }}</span>
        @endif
        <p>Student ID: <span class="highlight">{{ $student->student_code }}</span></p>
        <p>has successfully completed the prescribed course of study at</p>
        <p><b>Khmer Intellectual Academy (KIA)</b></p>
        <p>and is hereby awarded this certificate of graduation.</p>
        <br>
        <p>— &bull; —</p>
        <br>
        <p>យើងខ្ញុំបញ្ជាក់ថា
        <span class="highlight">{{ $student->name_en }}@if($student->name_km) ({{ $student->name_km }})@endif</span>
        បានបញ្ចប់ការសិក្សារបស់ខ្លួនដោយជោគជ័យ
        នៅ <b>Khmer Intellectual Academy (KIA)</b>
        ហើយត្រូវបានទទួលវិញ្ញាបនប័ត្របញ្ចប់ការសិក្សា។</p>
    </div>

    <div class="date">Issued on / ចេញ​ថ្ងៃ: {{ now()->format('d F Y') }}</div>
    <hr class="divider">

    <div class="seal-block">
        <div class="sig">Registrar / ការិយាល័យ</div>
        <div class="sig">Principal / នាយក</div>
        <div class="sig">Official Seal / ត្រាផ្លូវការ</div>
    </div>
</div>
</body>
</html>
