<x-app-layout>
    <x-slot name="title">{{ $homework->title }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $homework->title }}</h1>
            <p class="kia-page-sub">
                {{ $homework->section->name }} · {{ $homework->subject->name_en }} ·
                Due: {{ $homework->due_date->format('d M Y') }}
            </p>
        </div>
        @if($homework->attachment_path)
            <a href="{{ route('homework.download', $homework) }}" class="btn btn-ghost">⬇ Attachment</a>
        @endif
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    @if($homework->description)
        <div class="kia-card" style="margin-bottom:1rem">
            <div class="kia-card-body" style="white-space:pre-wrap">{{ $homework->description }}</div>
        </div>
    @endif

    {{-- Student submission form --}}
    @if($submission === null && auth()->user()->hasRole('student'))
        <div class="kia-card" style="max-width:640px;margin-bottom:1rem">
            <div class="kia-card-header"><h3 class="kia-card-title">Submit Your Work</h3></div>
            <div class="kia-card-body">
                <form method="POST" action="{{ route('homework.submit', $homework) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Note</label>
                        <textarea name="note" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">File (PDF/Word/Image, max 10MB)</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror"
                               accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                        @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <button class="btn btn-primary" type="submit">Submit</button>
                </form>
            </div>
        </div>
    @elseif($submission)
        <div class="kia-card" style="max-width:640px;margin-bottom:1rem">
            <div class="kia-card-header"><h3 class="kia-card-title">Your Submission</h3></div>
            <div class="kia-card-body">
                <p>Submitted: {{ $submission->submitted_at->format('d M Y H:i') }}
                    @if($submission->is_late) <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">LATE</span> @endif
                </p>
                @if($submission->grade !== null)
                    <p>Grade: <strong>{{ $submission->grade }}/100</strong></p>
                    @if($submission->feedback)<p>Feedback: {{ $submission->feedback }}</p>@endif
                @else
                    <p style="color:var(--text-muted)">Not graded yet.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Teacher grading panel --}}
    @if($submissions !== null)
        <div class="kia-card">
            <div class="kia-card-header"><h3 class="kia-card-title">Submissions ({{ $submissions->count() }})</h3></div>
            <div class="kia-table-wrap">
                <table class="kia-table">
                    <thead><tr><th>Student</th><th>Submitted</th><th>Grade</th><th>Feedback</th><th></th></tr></thead>
                    <tbody>
                    @foreach($submissions as $sub)
                        <tr>
                            <td>{{ $sub->student->name_en }}
                                @if($sub->is_late) <span class="kia-badge" style="background:var(--danger-light);color:var(--danger)">Late</span> @endif
                            </td>
                            <td>{{ $sub->submitted_at->format('d M H:i') }}</td>
                            <td>{{ $sub->grade ?? '—' }}</td>
                            <td>{{ $sub->feedback ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('homework-submissions.grade', $sub) }}"
                                      style="display:flex;gap:.4rem;align-items:center">
                                    @csrf
                                    <input type="number" name="grade" value="{{ $sub->grade }}" min="0" max="100"
                                           class="form-control" style="width:70px" placeholder="0-100">
                                    <input type="text" name="feedback" value="{{ $sub->feedback }}"
                                           class="form-control" style="width:180px" placeholder="Feedback">
                                    <button class="btn btn-primary" style="font-size:.75rem;padding:.3rem .6rem" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
