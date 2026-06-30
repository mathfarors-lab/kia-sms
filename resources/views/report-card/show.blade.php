<x-app-layout>
    <x-slot name="title">Report Card — {{ $student->name_en }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Report Card</h1>
            <p class="kia-page-sub">{{ $exam->name }} — {{ $student->name_en }}</p>
        </div>
        <a href="{{ route('report-card.pdf', [$exam, $student]) }}" class="btn btn-primary" target="_blank">
            ↓ Download PDF
        </a>
    </div>

    <div class="kia-card" style="max-width:760px">
        {{-- Header --}}
        <div style="text-align:center;padding:1.5rem 0 1rem;border-bottom:2px solid var(--royal)">
            <h2 style="color:var(--royal);margin:0">Khmer Intellectual Academy</h2>
            <p style="margin:0.25rem 0 0;color:var(--muted)">{{ $exam->name }} — {{ $exam->academicYear->name ?? '' }}</p>
        </div>

        {{-- Student Info --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;padding:1rem 0;border-bottom:1px solid var(--line)">
            <div><strong>Name:</strong> {{ $student->name_en }}</div>
            <div><strong>ឈ្មោះ:</strong> {{ $student->name_km }}</div>
            <div><strong>Student Code:</strong> {{ $student->student_code }}</div>
            <div><strong>Gender:</strong> {{ ucfirst($student->gender ?? '—') }}</div>
        </div>

        {{-- Marks Table --}}
        <table class="kia-table" style="margin-top:1rem">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Full Mark</th>
                    <th>Score</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($marks as $mark)
                    <tr>
                        <td>{{ $mark->subject->name_en }}</td>
                        <td>{{ $mark->subject->full_mark }}</td>
                        <td>{{ $mark->score }}</td>
                        <td><span class="badge badge-primary">{{ $mark->grade ?? '—' }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Result Summary --}}
        @if($result)
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;padding:1rem 0;margin-top:1rem;border-top:2px solid var(--line)">
            <div class="kia-stat-mini">
                <span class="kia-stat-mini__label">Average</span>
                <span class="kia-stat-mini__value">{{ $result->average }}</span>
            </div>
            <div class="kia-stat-mini">
                <span class="kia-stat-mini__label">GPA</span>
                <span class="kia-stat-mini__value">{{ $result->gpa }}</span>
            </div>
            <div class="kia-stat-mini">
                <span class="kia-stat-mini__label">Rank</span>
                <span class="kia-stat-mini__value">#{{ $result->rank }}</span>
            </div>
            <div class="kia-stat-mini">
                <span class="kia-stat-mini__label">Result</span>
                <span class="kia-stat-mini__value" style="color:{{ $result->result === 'pass' ? 'var(--ok)' : 'var(--bad)' }}">
                    {{ strtoupper($result->result) }}
                </span>
            </div>
        </div>
        @endif
    </div>

    <style>
    .kia-stat-mini { text-align:center; }
    .kia-stat-mini__label { display:block; font-size:.75rem; color:var(--muted); }
    .kia-stat-mini__value { display:block; font-size:1.5rem; font-weight:700; color:var(--ink); }
    </style>
</x-app-layout>
