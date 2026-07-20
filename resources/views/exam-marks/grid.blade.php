<x-app-layout>
    <x-slot name="title">Mark Entry — {{ $exam->name }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $exam->name }}</h1>
            <p class="kia-page-sub">Section: {{ $section->schoolClass->name ?? '' }} / {{ $section->name }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('exam-marks.export-excel', [$exam, $section]) }}" class="btn btn-ghost">↓ Excel</a>
            <a href="{{ route('exam-marks.export-pdf', [$exam, $section]) }}" class="btn btn-ghost" target="_blank">↓ PDF</a>
            <a href="{{ route('exam-marks.index') }}" class="btn btn-ghost">← Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>
    @endif

    @if($exam->is_published)
        <div class="kia-alert kia-alert-warn">This exam is published. Marks are locked.</div>
    @endif

    <div class="kia-card">
        <form method="POST" action="{{ route('exam-marks.save', [$exam, $section]) }}">
            @csrf
            <div class="kia-table-wrap">
                <table class="kia-table kia-marks-grid">
                    <thead>
                        <tr>
                            <th style="min-width:180px">Student</th>
                            @foreach($subjects as $subject)
                                <th>
                                    {{ app()->getLocale() === 'km' ? $subject->name_km : $subject->name_en }}
                                    <small style="display:block;color:var(--muted);font-weight:400">/{{ $subject->full_mark }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $student->name_en }}</td>
                                @foreach($subjects as $subject)
                                    @php
                                        $existing = $marks[$student->id][$subject->id] ?? null;
                                        $score    = $existing?->score;
                                    @endphp
                                    <td>
                                        <input
                                            type="number"
                                            name="marks[{{ $student->id }}][{{ $subject->id }}]"
                                            value="{{ $score }}"
                                            min="0"
                                            max="{{ $subject->full_mark }}"
                                            step="0.5"
                                            class="kia-input kia-mark-input"
                                            {{ $exam->is_published ? 'readonly' : '' }}
                                        >
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ $subjects->count() + 1 }}" class="kia-table-empty">No students enrolled in this section.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @unless($exam->is_published)
                <div class="kia-form-actions" style="padding:1rem 1.5rem">
                    <button type="submit" class="btn btn-primary">Save Marks</button>
                </div>
            @endunless
        </form>
    </div>

    <style>
    .kia-mark-input { width: 80px; text-align: center; padding: 0.25rem 0.5rem; }
    .kia-marks-grid th { text-align: center; }
    .kia-marks-grid td:first-child { text-align: left; }
    .kia-marks-grid td { text-align: center; }
    </style>
</x-app-layout>
