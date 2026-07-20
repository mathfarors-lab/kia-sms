<x-app-layout>
    <x-slot name="title">{{ __('discipline_records.section_title') }} — {{ $student->name_en }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('discipline_records.section_title') }}</h1>
            <p class="kia-page-sub">{{ $student->name_km ?: $student->name_en }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            @can('discipline.manage')
            <a href="{{ route('discipline-incidents.create', $student) }}" class="btn btn-primary">{{ __('discipline_records.log_incident') }}</a>
            @endcan
            <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">{{ __('Back') }}</a>
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('discipline_records.incident_date') }}</th>
                        <th>{{ __('discipline_records.type') }}</th>
                        <th>{{ __('discipline_records.description') }}</th>
                        <th>{{ __('discipline_records.action_taken') }}</th>
                        <th>{{ __('discipline_records.reported_by') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incidents as $incident)
                    <tr>
                        <td>{{ $incident->incident_date->format('d M Y') }}</td>
                        <td><span class="pill pill-warn">{{ __('discipline_records.type_'.$incident->type) }}</span></td>
                        <td style="max-width:260px;">{{ $incident->description }}</td>
                        <td style="max-width:220px;">{{ $incident->action_taken ?: '—' }}</td>
                        <td>{{ $incident->reportedBy?->name ?? '—' }}</td>
                        <td style="text-align:right;">
                            @can('discipline.manage')
                            <a href="{{ route('discipline-incidents.edit', $incident) }}" class="btn btn-ghost btn-sm">{{ __('Edit') }}</a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="kia-table-empty">{{ __('discipline_records.none_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
