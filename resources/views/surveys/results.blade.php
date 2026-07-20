<x-app-layout>
    <x-slot name="title">{{ __('surveys.results') }} — {{ $survey->title_en }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('surveys.results') }}</h1>
            <p class="kia-page-sub">{{ $survey->title_km ?: $survey->title_en }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('surveys.export-excel', $survey) }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('surveys.export-pdf', $survey) }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            <a href="{{ route('surveys.show', $survey) }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    <div class="kia-stats" style="margin-bottom:1.5rem">
        <div class="kia-stat">
            <div class="kia-stat-label">{{ __('surveys.response_rate') }}</div>
            <div class="kia-stat-value">{{ $completedCount }} / {{ $targetedCount }}</div>
        </div>
    </div>

    @foreach($results as $row)
    <div class="kia-card" style="margin-bottom:1rem">
        <div class="kia-card-header">
            <h3 class="kia-card-title">{{ $row['question']->question_text_km ?: $row['question']->question_text_en }}</h3>
        </div>
        <div class="kia-card-body">
            @if(isset($row['tally']))
                <div class="kia-table-wrap">
                    <table class="kia-table">
                        <thead><tr><th>{{ __('surveys.options') }}</th><th>{{ __('surveys.results') }}</th></tr></thead>
                        <tbody>
                            @forelse($row['tally'] as $option => $count)
                            <tr>
                                <td>{{ $option }}</td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.5rem">
                                        <div style="height:8px;width:120px;background:var(--surface-2);border-radius:4px;overflow:hidden">
                                            <div style="height:100%;width:{{ $row['count'] > 0 ? round($count / $row['count'] * 100) : 0 }}%;background:var(--primary);border-radius:4px"></div>
                                        </div>
                                        {{ $count }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="kia-table-empty">{{ __('surveys.no_answers_yet') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif(isset($row['average']))
                <div style="font-size:2rem;font-weight:700;">{{ $row['average'] !== null ? number_format($row['average'], 2) : '—' }}</div>
                <div style="color:var(--muted);font-size:.8rem;">{{ __('surveys.average') }} &middot; {{ $row['count'] }} {{ __('total') }}</div>
            @elseif(isset($row['answers']))
                @forelse($row['answers'] as $answer)
                <div style="padding:8px 0;border-bottom:1px solid var(--line);">
                    <div>{{ $answer['text'] }}</div>
                    @if($answer['author'])<div style="color:var(--muted);font-size:.78rem;">— {{ $answer['author'] }}</div>@endif
                </div>
                @empty
                <p style="color:var(--muted);font-size:.875rem;">{{ __('surveys.no_answers_yet') }}</p>
                @endforelse
            @endif
        </div>
    </div>
    @endforeach
</x-app-layout>
