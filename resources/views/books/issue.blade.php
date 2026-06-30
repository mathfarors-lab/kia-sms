@extends('layouts.app')
@section('title', 'Issue Book')
@section('content')
<div class="page-header"><h1>Issue: {{ $book->title }}</h1></div>
<form method="POST" action="{{ route('books.issue.store', $book) }}" class="card form-card">
    @csrf
    <div class="form-group">
        <label>Student</label>
        <select name="student_id" class="form-input" required>
            @foreach($students as $s)
                <option value="{{ $s->id }}">{{ $s->name_en }} ({{ $s->student_code }})</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Due Date</label>
        <input type="date" name="due_date" class="form-input" required min="{{ now()->addDay()->format('Y-m-d') }}">
    </div>
    <button class="btn btn-primary" type="submit">Issue</button>
    <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
