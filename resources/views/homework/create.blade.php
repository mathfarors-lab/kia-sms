@extends('layouts.app')
@section('title', 'Assign Homework')
@section('content')
<div class="page-header"><h1>Assign Homework</h1></div>
<form method="POST" action="{{ route('homework.store') }}" class="card form-card" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label>Section</label>
        <select name="section_id" class="form-input" required>
            @foreach($sections as $s)
                <option value="{{ $s->id }}">{{ $s->schoolClass->name }} – {{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Subject</label>
        <select name="subject_id" class="form-input" required>
            @foreach($subjects as $sub)
                <option value="{{ $sub->id }}">{{ $sub->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" class="form-input"></textarea>
    </div>
    <div class="form-group">
        <label>Due Date</label>
        <input type="date" name="due_date" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Attachment (PDF, Word, Image — max 10MB)</label>
        <input type="file" name="attachment" class="form-input" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
        @error('attachment')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="publish_now" value="1"> Publish immediately</label>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('homework.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
