<x-app-layout>
    <x-slot name="title">Exams</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Exams</h1>
            <p class="kia-page-sub">{{ $exams->total() }} total</p>
        </div>
        @can('exams.manage')
        <a href="{{ route('exams.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Exam
        </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                        <th></th>
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
                            <td class="kia-table-actions">
                                <a href="{{ route('exam-marks.index') }}?exam_id={{ $exam->id }}" class="btn btn-sm btn-ghost">Marks</a>
                                @can('exams.publish')
                                    @unless($exam->is_published)
                                        <form method="POST" action="{{ route('exams.publish', $exam) }}" onsubmit="return confirm('Compute & publish results?')">
                                            @csrf
                                            <button class="btn btn-sm btn-primary">Publish</button>
                                        </form>
                                    @endunless
                                @endcan
                                @can('exams.manage')
                                    @unless($exam->is_published)
                                        <a href="{{ route('exams.edit', $exam) }}" class="btn btn-sm btn-ghost">Edit</a>
                                        <form method="POST" action="{{ route('exams.destroy', $exam) }}" onsubmit="return confirm('Delete this exam?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="kia-table-empty">No exams yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="kia-pagination">{{ $exams->links() }}</div>
    </div>
</x-app-layout>
