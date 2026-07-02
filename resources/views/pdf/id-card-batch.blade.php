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
body { font-family: 'NotoKhmer', 'DejaVu Sans', sans-serif; font-size: 7.5pt; background: #fff; }
.page { padding: 10pt; }
.grid { width: 100%; }
.grid-row { display: table; width: 100%; margin-bottom: 10pt; }
.grid-cell { display: table-cell; width: 50%; padding: 0 5pt; }
.card {
    width: 242.55pt; height: 153.07pt;
    border: 0.5pt dashed #aaa;
    border-radius: 5pt;
    overflow: hidden;
    background: #fff;
    display: inline-block;
}
.card-header { background: #2B3A8F; color: #fff; padding: 3pt 7pt; font-size: 7.5pt; font-weight: bold; }
.card-header span { float: right; font-size: 6.5pt; color: #ECC531; font-weight: normal; }
.card-body { display: table; width: 100%; padding: 5pt; }
.photo-cell { display: table-cell; width: 50pt; vertical-align: top; }
.photo-cell img, .ph { width: 50pt; height: 63pt; object-fit: cover; border: 0.5pt solid #ddd; border-radius: 2pt; }
.ph { background: #e8eaf0; text-align: center; font-size: 7pt; color: #888; padding-top: 24pt; line-height: 1.3; }
.info-cell { display: table-cell; padding-left: 5pt; vertical-align: top; }
.info-name { font-size: 9pt; font-weight: bold; line-height: 1.2; }
.info-name-km { font-size: 8pt; color: #2B3A8F; line-height: 1.2; }
.info-meta { font-size: 7pt; color: #5b6079; margin-top: 3pt; line-height: 1.5; }
.qr-cell { display: table-cell; width: 44pt; vertical-align: bottom; text-align: right; }
.qr-cell img { width: 42pt; height: 42pt; }
.card-footer { background: #f0f3ff; text-align: center; font-size: 6pt; color: #5b6079; padding: 2pt; letter-spacing: 0.5pt; }
</style>
</head>
<body>
<div class="page">
    <p style="font-size:8pt;color:#5b6079;margin-bottom:8pt;">
        Batch ID Cards — {{ $section->schoolClass->name ?? '' }} / {{ $section->name }}
        ({{ $cards->count() }} cards) — {{ $year?->name }}
    </p>

    @php $chunks = $cards->chunk(2); @endphp
    @foreach($chunks as $row)
    <div class="grid-row">
        @foreach($row as $card)
        <div class="grid-cell">
            <div class="card">
                <div class="card-header">
                    KIA — Khmer Intellectual Academy
                    <span>{{ $card['year']?->name }}</span>
                </div>
                <div class="card-body">
                    <div class="photo-cell">
                        @if($card['photoUri'])
                            <img src="{{ $card['photoUri'] }}" alt="photo">
                        @else
                            <div class="ph">No<br>Photo</div>
                        @endif
                    </div>
                    <div class="info-cell">
                        <div class="info-name">{{ $card['student']->name_en }}</div>
                        @if($card['student']->name_km)
                        <div class="info-name-km">{{ $card['student']->name_km }}</div>
                        @endif
                        <div class="info-meta">
                            ID: {{ $card['student']->student_code }}<br>
                            {{ $card['section']->schoolClass->name ?? '' }} / {{ $card['section']->name }}<br>
                            {{ ucfirst($card['student']->gender ?? '') }}
                        </div>
                    </div>
                    <div class="qr-cell" data-qr-payload="{{ $card['student']->student_code }}">
                        <img src="{{ $card['qrUri'] }}" alt="QR">
                    </div>
                </div>
                <div class="card-footer">STUDENT ID / អត្តសញ្ញាណប័ណ្ណ</div>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
</body>
</html>
