<x-app-layout>
    <x-slot name="title">{{ __('academic_ranking.page_title_term') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('academic_ranking.page_title_term') }}</h1>
            <p class="kia-page-sub">{{ __('academic_ranking.subtitle_term') }}</p>
        </div>
    </div>

    @if($publishedPeriods->isEmpty())
        <div class="kia-card">
            <div class="kia-card-body">
                <div class="kia-empty">
                    <h3>{{ __('academic_ranking.no_published_terms') }}</h3>
                    <p>{{ __('academic_ranking.no_published_terms_hint') }}</p>
                    <a href="{{ route('term-results.index') }}" class="btn btn-primary" style="margin-top:12px;">{{ __('academic_ranking.go_to_term_results') }}</a>
                </div>
            </div>
        </div>
    @else
        @foreach($publishedPeriods as $yearName => $periods)
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ $yearName }}</h2>
            </div>
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead>
                        <tr>
                            <th>{{ __('academic_ranking.period') }}</th>
                            <th>{{ __('academic_ranking.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periods->unique(fn($p) => $p->semester) as $period)
                        @php
                            $slug = $period->semester === null ? 'annual' : (string) $period->semester;
                            $label = $period->semester === null ? __('academic_ranking.annual') : __('academic_ranking.semester_n', ['n' => $period->semester]);
                        @endphp
                        <tr>
                            <td style="font-weight:600;">{{ $label }}</td>
                            <td>
                                <a href="{{ route('term-ranking.show', [$period->academicYear, $slug]) }}" class="btn btn-sm btn-primary">
                                    {{ __('academic_ranking.view_ranking') }}
                                </a>
                                <a href="{{ route('term-ranking.excel', [$period->academicYear, $slug]) }}" class="btn btn-sm btn-ghost">
                                    {{ __('academic_ranking.download_excel') }}
                                </a>
                                <a href="{{ route('term-ranking.pdf', [$period->academicYear, $slug]) }}" class="btn btn-sm btn-ghost" target="_blank">
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
