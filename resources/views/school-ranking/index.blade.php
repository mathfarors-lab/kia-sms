<x-app-layout>
    <x-slot name="title">{{ __('academic_ranking.page_title_exam') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('academic_ranking.page_title_exam') }}</h1>
            <p class="kia-page-sub">{{ __('academic_ranking.subtitle_exam') }}</p>
        </div>
    </div>

    @if($publishedExams->isEmpty())
        <div class="kia-card">
            <div class="kia-card-body">
                <div class="kia-empty">
                    <h3>{{ __('academic_ranking.no_published_exams') }}</h3>
                    <p>{{ __('academic_ranking.no_published_exams_hint') }}</p>
                    <a href="{{ route('exams.index') }}" class="btn btn-primary" style="margin-top:12px;">{{ __('academic_ranking.go_to_exams') }}</a>
                </div>
            </div>
        </div>
    @else
        @foreach($publishedExams as $yearName => $exams)
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ $yearName }}</h2>
            </div>
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th>{{ __('academic_ranking.exam') }}</th>
                            <th>{{ __('academic_ranking.type') }}</th>
                            <th>{{ __('academic_ranking.date') }}</th>
                            <th>{{ __('academic_ranking.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $exam)
                        <tr>
                            <td style="font-weight:600;">{{ $exam->name }}</td>
                            <td>
                                <span class="pill {{ match($exam->type) {
                                    'monthly'  => 'pill-info',
                                    'midterm'  => 'pill-warn',
                                    'final'    => 'pill-ok',
                                    default    => 'pill-muted',
                                } }}">{{ ucfirst($exam->type) }}</span>
                            </td>
                            <td style="color:var(--muted);">{{ $exam->exam_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('school-ranking.exam', $exam) }}" class="btn btn-sm btn-primary">
                                    {{ __('academic_ranking.view_ranking') }}
                                </a>
                                <a href="{{ route('school-ranking.exam.excel', $exam) }}" class="btn btn-sm btn-ghost">
                                    {{ __('academic_ranking.download_excel') }}
                                </a>
                                <a href="{{ route('school-ranking.exam.pdf', $exam) }}" class="btn btn-sm btn-ghost" target="_blank">
                                    {{ __('academic_ranking.download_pdf') }}
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    @endif
</x-app-layout>
