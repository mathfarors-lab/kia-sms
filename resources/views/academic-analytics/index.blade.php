<x-app-layout>
    <x-slot name="title">{{ __('academic_analytics.title') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('academic_analytics.title') }}</h1></div>
        <form method="GET" action="{{ route('academic-analytics.index') }}" style="display:flex;gap:.5rem;align-items:center">
            <select name="year_id" class="form-control" style="width:auto" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y->id }}" @selected($y->id === $year->id)>{{ $y->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="kia-card" style="padding:1.25rem;margin-bottom:1.5rem;max-width:260px;">
        <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">{{ __('academic_analytics.pass_rate') }}</div>
        <div style="font-size:2rem;font-weight:700;margin-top:.25rem;{{ $passRate !== null && $passRate < 50 ? 'color:var(--danger)' : '' }}">
            {{ $passRate !== null ? $passRate . '%' : '—' }}
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.25rem">{{ __('academic_analytics.pass_rate_note') }}</div>
    </div>

    {{-- Average by section --}}
    <div class="kia-card" style="margin-bottom:1rem">
        <div class="kia-card-header"><h3 class="kia-card-title">{{ __('academic_analytics.by_section') }}</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('academic_analytics.class') }}</th>
                        <th>{{ __('academic_analytics.section') }}</th>
                        <th>{{ __('academic_analytics.student_count') }}</th>
                        <th>{{ __('academic_analytics.avg_score') }}</th>
                        <th>{{ __('academic_analytics.avg_gpa') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bySection as $row)
                    <tr @if($loop->count > 1 && $loop->first) style="background:rgba(31,157,107,.06)" @elseif($loop->count > 1 && $loop->last) style="background:rgba(216,87,63,.06)" @endif>
                        <td>{{ $row->class_name }}</td>
                        <td>
                            {{ $row->section_name }}
                            @if($loop->count > 1 && $loop->first)<span class="pill pill-ok" style="margin-left:.5rem;">{{ __('academic_analytics.highest') }}</span>@endif
                            @if($loop->count > 1 && $loop->last)<span class="pill pill-bad" style="margin-left:.5rem;">{{ __('academic_analytics.lowest') }}</span>@endif
                        </td>
                        <td>{{ $row->student_count }}</td>
                        <td>{{ number_format($row->avg_score, 1) }}</td>
                        <td>{{ number_format($row->avg_gpa, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="kia-table-empty">{{ __('academic_analytics.no_data_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Average by subject --}}
    <div class="kia-card" style="margin-bottom:1rem">
        <div class="kia-card-header"><h3 class="kia-card-title">{{ __('academic_analytics.by_subject') }}</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('academic_analytics.subject') }}</th>
                        <th>{{ __('academic_analytics.avg_score') }}</th>
                        <th>{{ __('academic_analytics.marks_count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjectAverages as $row)
                    <tr @if($loop->count > 1 && $loop->first) style="background:rgba(31,157,107,.06)" @elseif($loop->count > 1 && $loop->last) style="background:rgba(216,87,63,.06)" @endif>
                        <td>
                            {{ $row->name_km ?: $row->name_en }}
                            @if($loop->count > 1 && $loop->first)<span class="pill pill-ok" style="margin-left:.5rem;">{{ __('academic_analytics.highest') }}</span>@endif
                            @if($loop->count > 1 && $loop->last)<span class="pill pill-bad" style="margin-left:.5rem;">{{ __('academic_analytics.lowest') }}</span>@endif
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <div style="height:8px;width:80px;background:var(--surface-2);border-radius:4px;overflow:hidden">
                                    <div style="height:100%;width:{{ min(100, round($row->avg_score)) }}%;background:var(--primary);border-radius:4px"></div>
                                </div>
                                {{ number_format($row->avg_score, 1) }}
                            </div>
                        </td>
                        <td>{{ $row->mark_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="kia-table-empty">{{ __('academic_analytics.no_data_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Grade distribution --}}
    <div class="kia-card">
        <div class="kia-card-header"><h3 class="kia-card-title">{{ __('academic_analytics.grade_distribution') }}</h3></div>
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>{{ __('academic_analytics.grade') }}</th><th>{{ __('academic_analytics.count') }}</th></tr></thead>
                <tbody>
                    @forelse($gradeDistribution as $row)
                    <tr><td>{{ $row->grade }}</td><td>{{ $row->total }}</td></tr>
                    @empty
                    <tr><td colspan="2" class="kia-table-empty">{{ __('academic_analytics.no_data_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
