@extends('layouts.app')
@section('title', $homework->title)
@section('content')
<div class="page-header">
    <h1>{{ $homework->title }}</h1>
    <small>{{ $homework->section->name }} · {{ $homework->subject->name }} · Due: {{ $homework->due_date->format('d M Y') }}</small>
</div>

<div class="card">
    <p>{{ $homework->description }}</p>
    @if($homework->attachment_path)
        <a href="{{ route('homework.download', $homework) }}" class="btn btn-secondary">⬇ Download Attachment</a>
    @endif
</div>

{{-- Student submission form --}}
@if($submission === null && auth()->user()->hasRole('student'))
    <div class="card mt-4">
        <h3>Submit Your Work</h3>
        <form method="POST" action="{{ route('homework.submit', $homework) }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Note</label>
                <textarea name="note" rows="3" class="form-input"></textarea>
            </div>
            <div class="form-group">
                <label>File (PDF, Word, Image — max 10MB)</label>
                <input type="file" name="file" class="form-input" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                @error('file')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Submit</button>
        </form>
    </div>
@elseif($submission)
    <div class="card mt-4">
        <h3>Your Submission</h3>
        <p>Submitted: {{ $submission->submitted_at->format('d M Y H:i') }}
            @if($submission->is_late) <span class="badge badge-danger">LATE</span> @endif
        </p>
        @if($submission->grade !== null)
            <p>Grade: <strong>{{ $submission->grade }}/100</strong></p>
            <p>Feedback: {{ $submission->feedback }}</p>
        @else
            <p class="text-muted">Not graded yet.</p>
        @endif
    </div>
@endif

{{-- Teacher grading panel --}}
@if($submissions !== null)
    <div class="card mt-4">
        <h3>Submissions ({{ $submissions->count() }})</h3>
        @foreach($submissions as $sub)
            <div class="list-item">
                <div>
                    <strong>{{ $sub->student->name_en }}</strong>
                    <small>{{ $sub->submitted_at->format('d M') }}</small>
                    @if($sub->is_late) <span class="badge badge-danger">Late</span> @endif
                </div>
                <form method="POST" action="{{ route('homework-submissions.grade', $sub) }}" class="inline-form">
                    @csrf
                    <input type="number" name="grade" value="{{ $sub->grade }}" min="0" max="100" class="form-input-sm" placeholder="Grade">
                    <input type="text" name="feedback" value="{{ $sub->feedback }}" class="form-input-sm" placeholder="Feedback">
                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                </form>
            </div>
        @endforeach
    </div>
@endif
@endsection
