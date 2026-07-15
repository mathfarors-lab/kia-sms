<x-app-layout>
    <x-slot name="title">{{ __('nav.timetables') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('nav.timetables') }}</h1>
            <p class="kia-page-sub">{{ __('timetable.picker_sub') }}</p>
        </div>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('nav.classes_sections') }}</th>
                        <th>{{ __('timetable.section') }}</th>
                        <th>{{ __('timetable.class_teacher') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sections as $section)
                    <tr>
                        <td>{{ $section->schoolClass->name ?? '—' }}</td>
                        <td>{{ $section->name }}</td>
                        <td>{{ $section->classTeacher?->user?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('timetable.show', $section) }}" class="btn btn-sm btn-primary">{{ __('nav.timetables') }}</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center">{{ __('timetable.no_sections') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
