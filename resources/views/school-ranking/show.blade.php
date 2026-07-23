<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $title }}</h1>
            <p class="kia-page-sub">{{ __('academic_ranking.subtitle_exam') }} — {{ $stats['total'] }} {{ __('academic_ranking.total_students') }}</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <a href="{{ route('school-ranking.exam.excel', $exam) }}" class="btn btn-ghost">{{ __('academic_ranking.download_excel') }}</a>
            <a href="{{ route('school-ranking.exam.pdf', $exam) }}" class="btn btn-ghost" target="_blank">{{ __('academic_ranking.download_pdf') }}</a>
            <a href="{{ route('school-ranking.index') }}" class="btn btn-ghost">{{ __('academic_ranking.back') }}</a>
        </div>
    </div>

    {{-- Stats row --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:20px;">
        <div class="kia-stat-card">
            <div class="kia-stat-value">{{ $stats['total'] }}</div>
            <div class="kia-stat-label">{{ __('academic_ranking.total_students') }}</div>
        </div>
        <div class="kia-stat-card">
            <div class="kia-stat-value" style="color:var(--success);">{{ $stats['pass'] }}</div>
            <div class="kia-stat-label">{{ __('academic_ranking.passed') }}</div>
        </div>
        <div class="kia-stat-card">
            <div class="kia-stat-value" style="color:var(--danger);">{{ $stats['fail'] }}</div>
            <div class="kia-stat-label">{{ __('academic_ranking.failed') }}</div>
        </div>
        <div class="kia-stat-card">
            <div class="kia-stat-value">{{ $stats['pass_rate'] }}%</div>
            <div class="kia-stat-label">{{ __('academic_ranking.pass_rate') }}</div>
        </div>
        <div class="kia-stat-card">
            <div class="kia-stat-value">{{ $stats['average'] }}</div>
            <div class="kia-stat-label">{{ __('academic_ranking.school_average') }}</div>
        </div>
        @if($stats['top'])
        <div class="kia-stat-card">
            <div class="kia-stat-value" style="font-size:1rem;font-weight:700;">
                {{ $stats['top']->name_km ?: $stats['top']->name_en }}
            </div>
            <div class="kia-stat-label">{{ __('academic_ranking.top_student') }} ({{ $stats['top']->average }}%)</div>
        </div>
        @endif
    </div>

    {{-- Grade filter --}}
    @if($classes->isNotEmpty())
    <div class="kia-card" style="margin-bottom:16px;">
        <div class="kia-card-body" style="padding:.75rem 1rem;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span style="font-size:.85rem;color:var(--muted);font-weight:500;">{{ __('academic_ranking.filter_by_grade') }}</span>
                <a href="{{ route('school-ranking.exam', $exam) }}"
                   class="btn btn-xs {{ !$filterClass ? 'btn-primary' : 'btn-outline' }}">{{ __('academic_ranking.all') }}</a>
                @foreach($classes as $cls)
                <a href="{{ route('school-ranking.exam', ['exam' => $exam, 'class' => $cls]) }}"
                   class="btn btn-xs {{ $filterClass === $cls ? 'btn-primary' : 'btn-outline' }}">{{ $cls }}</a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Ranking table --}}
    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th style="width:60px;text-align:center;">{{ __('academic_ranking.school_rank') }}</th>
                        <th style="width:60px;text-align:center;">{{ __('academic_ranking.grade_rank') }}</th>
                        <th>{{ __('academic_ranking.student') }}</th>
                        <th>{{ __('academic_ranking.grade_section') }}</th>
                        <th style="text-align:center;">{{ __('academic_ranking.roll_no') }}</th>
                        <th style="text-align:right;">{{ __('academic_ranking.total') }}</th>
                        <th style="text-align:right;">{{ __('academic_ranking.average') }}</th>
                        <th style="text-align:right;">{{ __('academic_ranking.gpa') }}</th>
                        <th style="text-align:center;">{{ __('academic_ranking.result') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ranking as $row)
                    <tr class="{{ $row->school_rank <= 3 ? 'kia-row-highlight' : '' }}">
                        <td style="text-align:center;font-weight:700;">
                            @if($row->school_rank == 1) 🥇
                            @elseif($row->school_rank == 2) 🥈
                            @elseif($row->school_rank == 3) 🥉
                            @else #{{ $row->school_rank }}
                            @endif
                        </td>
                        <td style="text-align:center;color:var(--muted);font-weight:600;">#{{ $row->class_rank }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $row->name_km ?: $row->name_en }}</div>
                            @if($row->name_km)
                            <div style="font-size:.8rem;color:var(--muted);">{{ $row->name_en }}</div>
                            @endif
                            <div style="font-size:.75rem;color:var(--muted);">{{ $row->student_code }}</div>
                        </td>
                        <td>
                            <span style="font-weight:600;">{{ $row->class_name ?? '—' }}</span>
                            @if($row->section_name)
                            <span style="color:var(--muted);"> / {{ $row->section_name }}</span>
                            @endif
                        </td>
                        <td style="text-align:center;color:var(--muted);">{{ $row->roll_no ?? '—' }}</td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ number_format($row->total, 1) }}</td>
                        <td style="text-align:right;font-weight:600;font-variant-numeric:tabular-nums;">{{ number_format($row->average, 1) }}%</td>
                        <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ number_format($row->gpa, 2) }}</td>
                        <td style="text-align:center;">
                            <span class="pill {{ $row->result === 'pass' ? 'pill-ok' : 'pill-danger' }}">
                                {{ strtoupper($row->result) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="kia-table-empty">{{ __('academic_ranking.no_results_exam') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
    .kia-stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .kia-stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1.1;
        color: var(--text);
    }
    .kia-stat-label {
        font-size: .78rem;
        color: var(--muted);
        margin-top: 4px;
    }
    .kia-row-highlight td {
        background: color-mix(in srgb, var(--primary) 5%, transparent);
    }
    </style>
</x-app-layout>
