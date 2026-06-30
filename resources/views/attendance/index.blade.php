<x-app-layout>
    <x-slot name="title">Attendance</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Attendance</h1>
            <p class="kia-page-sub">Today: {{ now()->format('d M Y') }}</p>
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
                        <th>Class</th>
                        <th>Teacher</th>
                        <th>Present Today</th>
                        <th>Absent Today</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sections as $section)
                    <tr>
                        <td>{{ $section->name }}</td>
                        <td>{{ $section->schoolClass->name }}</td>
                        <td>{{ $section->classTeacher?->user?->name ?? '—' }}</td>
                        <td><span class="badge badge-success">{{ $section->today_present }}</span></td>
                        <td><span class="badge badge-danger">{{ $section->today_absent }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('attendance.mark', $section) }}" class="btn btn-sm btn-primary">Mark Attendance</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center">No sections found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $sections->links() }}</div>
    </div>
</x-app-layout>
