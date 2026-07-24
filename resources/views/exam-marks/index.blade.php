<x-app-layout>
    <x-slot name="title">Exam Marks</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Exam Marks</h1>
            <p class="kia-page-sub">
                @if($isAdmin)
                    Enter and manage marks for all sections
                @else
                    Enter marks for your assigned sections
                @endif
                @if($activeYear)
                    — <strong>{{ $activeYear->name }}</strong>
                @endif
            </p>
        </div>
        @if($isAdmin)
        <div>
            @can(\App\Support\Permissions::TERM_RESULTS_MANAGE)
            <a href="{{ route('school-ranking.index') }}" class="btn btn-ghost">🏆 School Ranking</a>
            @endcan
        </div>
        @endif
    </div>

    <div class="kia-tabs">
        <a href="{{ route('exams.index') }}" class="kia-tab">Exams</a>
        <span class="kia-tab active">Mark Entry</span>
        <a href="{{ route('term-results.index') }}" class="kia-tab">Results</a>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    @if($sections->isEmpty())
        <div class="kia-card">
            <div class="kia-card-body">
                <div class="kia-empty">
                    <h3>No sections assigned</h3>
                    <p>You have no sections to enter marks for. Contact the administrator if this is incorrect.</p>
                </div>
            </div>
        </div>
    @elseif($exams->isEmpty())
        <div class="kia-card">
            <div class="kia-card-body">
                <div class="kia-empty">
                    <h3>No exams yet</h3>
                    <p>No exams have been created for the active academic year.</p>
                    @can(\App\Support\Permissions::EXAMS_MANAGE)
                    <a href="{{ route('exams.create') }}" class="btn btn-primary" style="margin-top:12px;">Create Exam</a>
                    @endcan
                </div>
            </div>
        </div>
    @else

    {{-- ── Monthly Exams ─────────────────────────────────────────── --}}
    @if($monthlyExams->isNotEmpty())
    <div style="margin-bottom:24px;">
        <div class="kia-section-label" style="margin-bottom:10px;">Monthly Exams</div>

        @foreach($monthlyExams as $exam)
        <div class="kia-card" style="margin-bottom:14px;">
            <div class="kia-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="kia-card-title" style="margin:0;">{{ $exam->name }}</h2>
                    @if($exam->exam_date)
                    <span style="font-size:.8rem;color:var(--muted);">{{ $exam->exam_date->format('d M Y') }}</span>
                    @endif
                    @if($exam->is_published)
                    <span class="pill pill-ok">Published</span>
                    @else
                    <span class="pill pill-warn">Draft</span>
                    @endif
                </div>
                @can(\App\Support\Permissions::EXAMS_MANAGE)
                <a href="{{ route('exams.edit', $exam) }}" class="btn btn-xs btn-ghost">Edit Exam</a>
                @endcan
            </div>

            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th>Grade / Section</th>
                            <th style="text-align:center;">Students</th>
                            <th style="text-align:center;">Marks Progress</th>
                            <th style="text-align:center;">Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasSections = false; @endphp
                        @foreach($sections as $section)
                        @if(isset($completion[$exam->id][$section->id]))
                        @php
                            $hasSections = true;
                            $c = $completion[$exam->id][$section->id];
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $section->schoolClass->name ?? '—' }}</strong>
                                <span style="color:var(--muted);"> / {{ $section->name }}</span>
                            </td>
                            <td style="text-align:center;">{{ $c['students'] }}</td>
                            <td style="text-align:center;min-width:140px;">
                                @php
                                    $pct = $c['expected'] > 0 ? min(100, round($c['entered'] / $c['expected'] * 100)) : 0;
                                @endphp
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:var(--border);border-radius:4px;height:6px;overflow:hidden;">
                                        <div style="width:{{ $pct }}%;background:{{ $c['status'] === 'complete' ? 'var(--success)' : ($c['status'] === 'partial' ? 'var(--warning, #f59e0b)' : 'var(--border)') }};height:100%;border-radius:4px;"></div>
                                    </div>
                                    <span style="font-size:.78rem;color:var(--muted);white-space:nowrap;">{{ $c['entered'] }}/{{ $c['expected'] }}</span>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($c['status'] === 'complete')
                                    <span class="pill pill-ok">Complete</span>
                                @elseif($c['status'] === 'partial')
                                    <span class="pill pill-warn">Partial</span>
                                @elseif($c['status'] === 'no-subjects')
                                    <span class="pill pill-muted" title="No subjects assigned to this class yet">No subjects</span>
                                @else
                                    <span class="pill pill-muted">Not started</span>
                                @endif
                            </td>
                            <td>
                                @if($c['status'] === 'no-subjects')
                                    <span style="font-size:.8rem;color:var(--muted);">Assign subjects to this class first</span>
                                @elseif($exam->is_published)
                                    <span style="font-size:.8rem;color:var(--muted);">Locked (published)</span>
                                @else
                                <a href="{{ route('exam-marks.grid', [$exam, $section]) }}" class="btn btn-sm btn-primary">
                                    {{ $c['status'] === 'empty' ? 'Enter Marks' : 'Edit Marks' }}
                                </a>
                                @endif
                                <a href="{{ route('exam-marks.export-pdf', [$exam, $section]) }}" class="btn btn-sm btn-ghost" target="_blank">↓ PDF</a>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        @if(!$hasSections)
                        <tr><td colspan="5" class="kia-table-empty">No enrolled sections for this exam.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Midterm & Final Exams ─────────────────────────────────── --}}
    @if($otherExams->isNotEmpty())
    <div style="margin-bottom:24px;">
        <div class="kia-section-label" style="margin-bottom:10px;">Midterm & Final Exams</div>

        @foreach($otherExams as $exam)
        <div class="kia-card" style="margin-bottom:14px;">
            <div class="kia-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <h2 class="kia-card-title" style="margin:0;">{{ $exam->name }}</h2>
                    <span class="pill {{ $exam->type === 'final' ? 'pill-info' : 'pill-muted' }}">{{ ucfirst($exam->type) }}</span>
                    @if($exam->exam_date)
                    <span style="font-size:.8rem;color:var(--muted);">{{ $exam->exam_date->format('d M Y') }}</span>
                    @endif
                    @if($exam->is_published)
                    <span class="pill pill-ok">Published</span>
                    @else
                    <span class="pill pill-warn">Draft</span>
                    @endif
                </div>
                @can(\App\Support\Permissions::EXAMS_MANAGE)
                <a href="{{ route('exams.edit', $exam) }}" class="btn btn-xs btn-ghost">Edit Exam</a>
                @endcan
            </div>

            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th>Grade / Section</th>
                            <th style="text-align:center;">Students</th>
                            <th style="text-align:center;">Marks Progress</th>
                            <th style="text-align:center;">Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $hasSections = false; @endphp
                        @foreach($sections as $section)
                        @if(isset($completion[$exam->id][$section->id]))
                        @php
                            $hasSections = true;
                            $c = $completion[$exam->id][$section->id];
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $section->schoolClass->name ?? '—' }}</strong>
                                <span style="color:var(--muted);"> / {{ $section->name }}</span>
                            </td>
                            <td style="text-align:center;">{{ $c['students'] }}</td>
                            <td style="text-align:center;min-width:140px;">
                                @php $pct = $c['expected'] > 0 ? min(100, round($c['entered'] / $c['expected'] * 100)) : 0; @endphp
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:var(--border);border-radius:4px;height:6px;overflow:hidden;">
                                        <div style="width:{{ $pct }}%;background:{{ $c['status'] === 'complete' ? 'var(--success)' : ($c['status'] === 'partial' ? '#f59e0b' : 'var(--border)') }};height:100%;border-radius:4px;"></div>
                                    </div>
                                    <span style="font-size:.78rem;color:var(--muted);white-space:nowrap;">{{ $c['entered'] }}/{{ $c['expected'] }}</span>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                @if($c['status'] === 'complete')
                                    <span class="pill pill-ok">Complete</span>
                                @elseif($c['status'] === 'partial')
                                    <span class="pill pill-warn">Partial</span>
                                @elseif($c['status'] === 'no-subjects')
                                    <span class="pill pill-muted" title="No subjects assigned to this class yet">No subjects</span>
                                @else
                                    <span class="pill pill-muted">Not started</span>
                                @endif
                            </td>
                            <td>
                                @if($c['status'] === 'no-subjects')
                                    <span style="font-size:.8rem;color:var(--muted);">Assign subjects to this class first</span>
                                @elseif($exam->is_published)
                                    <span style="font-size:.8rem;color:var(--muted);">Locked (published)</span>
                                @else
                                <a href="{{ route('exam-marks.grid', [$exam, $section]) }}" class="btn btn-sm btn-primary">
                                    {{ $c['status'] === 'empty' ? 'Enter Marks' : 'Edit Marks' }}
                                </a>
                                @endif
                                <a href="{{ route('exam-marks.export-pdf', [$exam, $section]) }}" class="btn btn-sm btn-ghost" target="_blank">↓ PDF</a>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        @if(!$hasSections)
                        <tr><td colspan="5" class="kia-table-empty">No enrolled sections for this exam.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @endif

    <style>
    .kia-section-label {
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 0 2px;
    }
    </style>
</x-app-layout>
