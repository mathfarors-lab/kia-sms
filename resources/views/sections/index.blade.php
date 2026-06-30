<x-app-layout>
    <x-slot name="title">Sections — {{ $class->name }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Sections — {{ $class->name }}</h1>
            <p class="kia-page-sub">{{ $sections->count() }} sections</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <a href="{{ route('classes.sections.create', $class) }}" class="btn btn-primary">Add Section</a>
            <a href="{{ route('classes.show', $class) }}" class="btn btn-ghost">Back</a>
        </div>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Class Teacher</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sections as $section)
                    <tr>
                        <td>{{ $section->name }}</td>
                        <td>{{ $section->classTeacher?->user?->name ?? '—' }}</td>
                        <td class="text-right">
                            <a href="{{ route('timetable.show', $section) }}" class="btn btn-sm btn-ghost">Timetable</a>
                            <a href="{{ route('attendance.mark', $section) }}" class="btn btn-sm btn-ghost">Attendance</a>
                            <a href="{{ route('classes.sections.edit', [$class, $section]) }}" class="btn btn-sm btn-ghost">Edit</a>
                            <form method="POST" action="{{ route('classes.sections.destroy', [$class, $section]) }}" style="display:inline" onsubmit="return confirm('Delete section?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center">No sections yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
