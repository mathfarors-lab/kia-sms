<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@font-face { font-family:'NotoKhmer'; src:url("{{ storage_path('fonts/NotoSansKhmer.ttf') }}") format('truetype'); }
body { font-family:'NotoKhmer','DejaVu Sans',sans-serif; font-size:10px; color:#1b1f33; }
h1 { font-size:14px; color:#2B3A8F; text-align:center; margin-bottom:4px; }
p.sub { text-align:center; color:#5b6079; font-size:9px; margin-bottom:16px; }
.q-block { margin-bottom:14px; }
.q-title { font-size:11px; font-weight:bold; color:#2B3A8F; background:#f0f3ff; padding:5px 8px; border-left:3px solid #2B3A8F; margin-bottom:6px; }
table { width:100%; border-collapse:collapse; }
th { background:#2B3A8F; color:#fff; padding:4px 8px; text-align:left; font-size:9px; }
td { padding:4px 8px; border-bottom:1px solid #E4E7F4; font-size:9px; }
tr:nth-child(even) td { background:#F6F7FC; }
.avg { font-size:16px; font-weight:bold; color:#2B3A8F; }
</style>
</head>
<body>
<h1>Khmer Intellectual Academy — {{ $survey->title_en }}</h1>
<p class="sub">Survey Results &middot; Generated {{ now()->format('d M Y') }} &middot; {{ $survey->is_anonymous ? 'Anonymous' : 'Non-anonymous' }}</p>

@foreach($results as $row)
<div class="q-block">
    <div class="q-title">{{ $row['question']->question_text_en }} ({{ $row['count'] }} responses)</div>

    @if(isset($row['tally']))
    <table>
        <thead><tr><th>Answer</th><th>Count</th></tr></thead>
        <tbody>
            @forelse($row['tally'] as $option => $count)
            <tr><td>{{ $option }}</td><td>{{ $count }}</td></tr>
            @empty
            <tr><td colspan="2">No answers.</td></tr>
            @endforelse
        </tbody>
    </table>
    @elseif(isset($row['average']))
    <div class="avg">{{ $row['average'] !== null ? number_format($row['average'], 2) : '—' }}</div>
    @elseif(isset($row['answers']))
    <table>
        <thead><tr><th>Answer</th>@if(!$survey->is_anonymous)<th>Respondent</th>@endif</tr></thead>
        <tbody>
            @forelse($row['answers'] as $a)
            <tr><td>{{ $a['text'] }}</td>@if(!$survey->is_anonymous)<td>{{ $a['author'] ?? '—' }}</td>@endif</tr>
            @empty
            <tr><td colspan="2">No answers.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif
</div>
@endforeach
</body>
</html>
