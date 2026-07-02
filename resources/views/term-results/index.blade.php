<x-app-layout>
    <x-slot name="title">{{ __('term_results.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('term_results.title') }}</h1>
            <p class="kia-page-sub">{{ __('term_results.subtitle') }}</p>
        </div>
    </div>

    {{-- Year / Semester selector --}}
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body">
            <form method="GET" action="{{ route('term-results.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div>
                    <label class="kia-label">{{ __('term_results.academic_year') }}</label>
                    <select name="year" class="kia-select" onchange="this.form.submit()">
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" @selected($selectedYear?->id === $year->id)>{{ $year->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="kia-label">{{ __('term_results.semester') }}</label>
                    <select name="semester" class="kia-select" onchange="this.form.submit()">
                        <option value="1"        @selected($selectedSemester === 1)>{{ __('term_results.semester_1') }}</option>
                        <option value="2"        @selected($selectedSemester === 2)>{{ __('term_results.semester_2') }}</option>
                        <option value="annual"   @selected($selectedSemester === null)>{{ __('term_results.annual') }}</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedYear)
    {{-- Admin/Principal actions --}}
    @canany([App\Support\Permissions::TERM_RESULTS_MANAGE, App\Support\Permissions::TERM_RESULTS_PUBLISH])
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-body" style="display:flex;gap:10px;flex-wrap:wrap;">
            @can(App\Support\Permissions::TERM_RESULTS_MANAGE)
            <form method="POST" action="{{ route('term-results.compute') }}">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedYear->id }}">
                <input type="hidden" name="semester" value="{{ $selectedSemester ?? 'annual' }}">
                <button type="submit" class="btn btn-outline"
                    onclick="return confirm('{{ __('term_results.confirm_compute') }}')">
                    {{ __('term_results.compute') }}
                </button>
            </form>
            <form method="POST" action="{{ route('term-results.finalize', $selectedYear) }}">
                @csrf
                <input type="hidden" name="semester" value="{{ $selectedSemester ?? 'annual' }}">
                <button type="submit" class="btn btn-outline"
                    onclick="return confirm('{{ __('term_results.confirm_finalize') }}')">
                    {{ __('term_results.finalize') }}
                </button>
            </form>
            @endcan
            @can(App\Support\Permissions::TERM_RESULTS_PUBLISH)
            <form method="POST" action="{{ route('term-results.publish', $selectedYear) }}">
                @csrf
                <input type="hidden" name="semester" value="{{ $selectedSemester ?? 'annual' }}">
                <button type="submit" class="btn btn-primary"
                    onclick="return confirm('{{ __('term_results.confirm_publish') }}')">
                    {{ __('term_results.publish') }}
                </button>
            </form>
            @endcan
        </div>
    </div>
    @endcanany
    @endif

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
    @endif

    @foreach($sections as $section)
    @php $rows = $termResults->get($section->id, collect()); @endphp
    <div class="kia-card" style="margin-bottom:20px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ $section->schoolClass->name ?? '—' }} — {{ $section->name }}</h2>
            <span class="pill pill-muted">{{ $rows->count() }} {{ __('term_results.students') }}</span>
        </div>
        @if($rows->isEmpty())
            <div class="kia-card-body"><p class="text-muted">{{ __('term_results.no_results') }}</p></div>
        @else
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('term_results.student') }}</th>
                        <th>{{ __('term_results.total') }}</th>
                        <th>{{ __('term_results.average') }}</th>
                        <th>{{ __('term_results.gpa') }}</th>
                        <th>{{ __('term_results.rank') }}</th>
                        <th>{{ __('term_results.result') }}</th>
                        <th>{{ __('term_results.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $tr)
                    <tr>
                        <td class="mono" style="color:var(--muted);">{{ $loop->iteration }}</td>
                        <td>
                            <div style="font-weight:600;">{{ $tr->student->name_km ?: $tr->student->name_en }}</div>
                            @if($tr->student->name_km)
                            <div style="font-size:.8rem;color:var(--muted);">{{ $tr->student->name_en }}</div>
                            @endif
                        </td>
                        <td>{{ $tr->total }}</td>
                        <td>{{ $tr->average }}</td>
                        <td>{{ $tr->gpa }}</td>
                        <td style="font-weight:700;">#{{ $tr->rank }}</td>
                        <td>
                            <span class="pill {{ $tr->result === 'pass' ? 'pill-ok' : 'pill-danger' }}">
                                {{ strtoupper($tr->result) }}
                            </span>
                            @if($tr->has_missing_marks)
                                <span class="pill pill-warn" style="margin-left:4px;" title="{{ __('term_results.missing_marks_flag') }}">⚠</span>
                            @endif
                        </td>
                        <td>
                            @if($tr->is_finalized)
                                <span class="pill pill-muted">{{ __('term_results.finalized') }}</span>
                            @endif
                            @if($tr->is_published)
                                <span class="pill pill-ok">{{ __('term_results.published') }}</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('term-results.show', [$selectedYear, $selectedSemester ?? 'annual', $tr->student]) }}"
                               class="btn btn-xs btn-outline">{{ __('term_results.view') }}</a>
                            <a href="{{ route('term-results.pdf', [$selectedYear, $selectedSemester ?? 'annual', $tr->student]) }}"
                               class="btn btn-xs btn-ghost">PDF</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endforeach

    @if($sections->isEmpty() && $selectedYear)
        <div class="kia-card">
            <div class="kia-card-body">
                <div class="kia-empty">
                    <h3>{{ __('term_results.no_sections') }}</h3>
                    <p>{{ __('term_results.no_sections_hint') }}</p>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
