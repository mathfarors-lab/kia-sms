<x-app-layout>
    <x-slot name="title">{{ __('term_results.report_card') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $termResult->semesterLabel() }} — {{ __('term_results.report_card') }}</h1>
            <p class="kia-page-sub">{{ $academicYear->name }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            @can('term-results.manage')
            <a href="{{ route('term-results.remark.edit', [$academicYear, $semesterSlug, $student]) }}"
               class="btn btn-outline">{{ __('term_results.edit_remark') }}</a>
            @endcan
            <a href="{{ route('term-results.pdf', [$academicYear, $semesterSlug, $student]) }}"
               class="btn btn-primary">{{ __('term_results.download_pdf') }}</a>
            <a href="{{ route('term-results.index', ['year' => $academicYear->id, 'semester' => $semesterSlug]) }}"
               class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    {{-- Student info --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                <div>
                    <div class="kia-stat-label">{{ __('term_results.student') }}</div>
                    <div style="font-weight:700;font-size:1.1rem;">{{ $student->name_km ?: $student->name_en }}</div>
                    @if($student->name_km)<div style="color:var(--muted);font-size:.85rem;">{{ $student->name_en }}</div>@endif
                </div>
                <div>
                    <div class="kia-stat-label">{{ __('term_results.student_code') }}</div>
                    <div class="mono">{{ $student->student_code }}</div>
                </div>
                <div>
                    <div class="kia-stat-label">{{ __('term_results.period') }}</div>
                    <div style="font-weight:600;">{{ $termResult->semesterLabel() }}</div>
                </div>
                @if($termResult->section)
                <div>
                    <div class="kia-stat-label">{{ __('term_results.section') }}</div>
                    <div>{{ $termResult->section->schoolClass->name ?? '—' }} / {{ $termResult->section->name }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Component exam marks --}}
    @foreach($exams as $exam)
    <div class="kia-card" style="margin-bottom:16px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ $exam->name }}</h2>
            <div style="display:flex;gap:8px;align-items:center;">
                <span class="pill pill-muted">{{ __('term_results.weight') }}: {{ $exam->weight }}</span>
                @if($exam->semester)
                    <span class="pill pill-muted">S{{ $exam->semester }}</span>
                @endif
            </div>
        </div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('term_results.subject') }}</th>
                        <th>{{ __('term_results.full_mark') }}</th>
                        <th>{{ __('term_results.coef') }}</th>
                        <th>{{ __('term_results.score') }}</th>
                        <th>{{ __('term_results.grade') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exam->marks as $mark)
                    <tr>
                        <td>
                            {{ $mark->subject->name_en }}
                            @if($mark->subject->name_km)
                                <br><small style="color:var(--muted);">{{ $mark->subject->name_km }}</small>
                            @endif
                        </td>
                        <td>{{ $mark->subject->full_mark }}</td>
                        <td>{{ $mark->subject->coefficient }}</td>
                        <td>{{ $mark->score }}</td>
                        <td><span class="pill pill-muted">{{ $mark->grade ?? '—' }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center" style="color:var(--warn);">
                        {{ __('term_results.missing_marks_flag') }}
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

    {{-- Consolidated summary --}}
    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('term_results.consolidated_result') }}</h2></div>
        <div class="kia-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;text-align:center;">
                <div style="padding:16px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--royal);">{{ $termResult->total }}</div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ __('term_results.total') }}</div>
                </div>
                <div style="padding:16px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--royal);">{{ $termResult->average }}</div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ __('term_results.average') }}</div>
                </div>
                <div style="padding:16px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--royal);">{{ $termResult->gpa }}</div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ __('term_results.gpa') }}</div>
                </div>
                <div style="padding:16px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.8rem;font-weight:700;color:var(--royal);">#{{ $termResult->rank }}</div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ __('term_results.rank') }}</div>
                </div>
                <div style="padding:16px;background:var(--bg-alt);border-radius:8px;">
                    <div style="font-size:1.8rem;font-weight:700;color:{{ $termResult->result === 'pass' ? 'var(--ok)' : 'var(--danger)' }};">
                        {{ strtoupper($termResult->result) }}
                    </div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ __('term_results.result') }}</div>
                </div>
            </div>

            @if($termResult->has_missing_marks)
            <div style="margin-top:16px;padding:12px;background:#fffbe6;border:1px solid #f0c040;border-radius:6px;color:#7a5c00;">
                ⚠ {{ __('term_results.missing_marks_warning') }}
            </div>
            @endif

            @if($termResult->teacher_remark)
            <div style="margin-top:16px;padding:12px;background:var(--bg-alt);border-radius:6px;">
                <div style="font-size:.8rem;color:var(--muted);margin-bottom:4px;">{{ __('term_results.teacher_remark') }}</div>
                <div>{{ $termResult->teacher_remark }}</div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
