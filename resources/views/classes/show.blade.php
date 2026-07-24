<x-app-layout>
    <x-slot name="title">{{ $class->name }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $class->name }}</h1>
            <p class="kia-page-sub">{{ $class->level ?? '' }} — Capacity: {{ $class->capacity }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('classes.sections.index', $class) }}" class="btn btn-outline">Manage Sections</a>
            <a href="{{ route('classes.edit', $class) }}" class="btn btn-ghost">Edit</a>
            <a href="{{ route('classes.index') }}" class="btn btn-ghost">Back</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <div class="kia-card">
            <h2 class="kia-section-title" style="padding:1rem 1.25rem .5rem">Sections ({{ $class->sections->count() }})</h2>
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead><tr><th>Section</th><th>Class Teacher</th><th>Students</th><th></th></tr></thead>
                    <tbody>
                        @forelse($class->sections as $section)
                        <tr>
                            <td>{{ $section->name }}</td>
                            <td>{{ $section->classTeacher?->user?->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('sections.show', $section) }}" class="btn btn-sm btn-ghost">
                                    {{ $section->students_count }} students
                                </a>
                            </td>
                            <td><a href="{{ route('timetable.show', $section) }}" class="btn btn-sm btn-ghost">Timetable</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No sections.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="kia-card">
            <h2 class="kia-section-title" style="padding:1rem 1.25rem .5rem">Assigned Subjects ({{ $class->subjects->count() }})</h2>
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead><tr><th>Subject</th><th>Code</th></tr></thead>
                    <tbody>
                        @forelse($class->subjects as $subject)
                        <tr>
                            <td>{{ $subject->name_en }}</td>
                            <td><code>{{ $subject->code }}</code></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center">No subjects assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
