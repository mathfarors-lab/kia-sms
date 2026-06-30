<x-app-layout>
    <x-slot name="title">Exam Marks</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Exam Marks</h1>
            <p class="kia-page-sub">Select an exam and section to enter marks</p>
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
                        <th>Exam</th>
                        <th>Type</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($exams as $exam)
                        <tr>
                            <td>{{ $exam->name }}</td>
                            <td><span class="badge badge-secondary">{{ ucfirst($exam->type) }}</span></td>
                            <td>{{ $exam->academicYear->name ?? '—' }}</td>
                            <td>
                                @if($exam->is_published)
                                    <span class="badge badge-success">Published</span>
                                @else
                                    <span class="badge badge-warn">Draft</span>
                                @endif
                            </td>
                            <td>
                                {{-- Link to section picker; for now navigate to first section or show a section dropdown --}}
                                <a href="{{ route('exams.index') }}#exam-{{ $exam->id }}" class="btn btn-sm btn-ghost">Select Section</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kia-table-empty">No exams.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
