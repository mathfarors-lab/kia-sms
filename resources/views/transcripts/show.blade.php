<x-app-layout>
    <x-slot name="title">{{ __('documents.transcript') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('documents.transcript') }}</h1>
            <p class="kia-page-sub">{{ $student->name_km ?: $student->name_en }} — {{ $student->student_code }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('transcripts.pdf', $student) }}" class="btn btn-primary">{{ __('documents.download_pdf') }}</a>
            <a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    {{-- Student info --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
                <div><div class="kia-stat-label">{{ __('documents.name_en') }}</div><div style="font-weight:600;">{{ $student->name_en }}</div></div>
                @if($student->name_km)
                <div><div class="kia-stat-label">{{ __('documents.name_km') }}</div><div style="font-weight:600;">{{ $student->name_km }}</div></div>
                @endif
                <div><div class="kia-stat-label">{{ __('documents.student_code') }}</div><div class="mono">{{ $student->student_code }}</div></div>
                <div><div class="kia-stat-label">{{ __('documents.gender') }}</div><div>{{ ucfirst($student->gender ?? '—') }}</div></div>
            </div>
        </div>
    </div>

    @forelse($yearBlocks as $block)
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ $block['year']->name }}</h2>
        </div>

        {{-- Per-subject table --}}
        @if($block['subjectsBySem'])
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('documents.subject') }}</th>
                        @if(isset($block['subjectsBySem'][1]))<th>S1 Avg</th><th>S1 Grade</th>@endif
                        @if(isset($block['subjectsBySem'][2]))<th>S2 Avg</th><th>S2 Grade</th>@endif
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
                        <td>
                            {{ $subject->name_en }}
                            @if($subject->name_km)<br><small style="color:var(--muted)">{{ $subject->name_km }}</small>@endif
                        </td>
                        @if(isset($block['subjectsBySem'][1]))
                        <td>{{ $s1['average'] ?? '—' }}</td>
                        <td>{{ $s1 ? ($s1['grade'] ?? '—') : '—' }}</td>
                        @endif
                        @if(isset($block['subjectsBySem'][2]))
                        <td>{{ $s2['average'] ?? '—' }}</td>
                        <td>{{ $s2 ? ($s2['grade'] ?? '—') : '—' }}</td>
                        @endif
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Term summary strip --}}
        <div class="kia-card-body">
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                @foreach([1 => 'S1', 2 => 'S2', 'annual' => 'Annual'] as $key => $label)
                @if($tr = $block['termResults']->get($key))
                <div style="flex:1;min-width:160px;padding:10px;background:var(--bg-alt);border-radius:6px;">
                    <div style="font-size:.75rem;font-weight:600;color:var(--muted);margin-bottom:6px;">{{ $label }}</div>
                    <div style="font-size:1.2rem;font-weight:700;color:var(--royal);">{{ $tr->average }}</div>
                    <div style="font-size:.75rem;color:var(--muted);">GPA {{ $tr->gpa }} &bull; Rank #{{ $tr->rank }}</div>
                    <span class="pill {{ $tr->result === 'pass' ? 'pill-ok' : 'pill-danger' }}" style="margin-top:4px;">
                        {{ strtoupper($tr->result) }}
                    </span>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
    @empty
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('documents.no_published_results') }}</h3>
                <p>{{ __('documents.no_published_results_hint') }}</p>
            </div>
        </div>
    </div>
    @endforelse
</x-app-layout>
