@extends('layouts.app')
@section('title', 'New Announcement')
@section('content')
<div class="page-header"><h1>New Announcement</h1></div>

<form method="POST" action="{{ route('announcements.store') }}" class="card form-card">
    @csrf
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title') }}" class="form-input" required>
        @error('title')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <div class="form-group">
        <label>Body (English)</label>
        <textarea name="body_en" rows="5" class="form-input" required>{{ old('body_en') }}</textarea>
    </div>
    <div class="form-group">
        <label>Body (Khmer)</label>
        <textarea name="body_km" rows="5" class="form-input">{{ old('body_km') }}</textarea>
    </div>
    <div class="form-group">
        <label>Audience</label>
        <select name="audience" class="form-input" id="audience-select">
            <option value="all">All</option>
            <option value="class">Specific Class (Section)</option>
            <option value="grade">Specific Grade</option>
        </select>
    </div>
    <div class="form-group" id="target-class" style="display:none">
        <label>Section</label>
        <select name="target_id" class="form-input">
            @foreach($sections as $s)
                <option value="{{ $s->id }}">{{ $s->schoolClass->name }} – {{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" id="target-grade" style="display:none">
        <label>Grade (Class)</label>
        <select name="target_id" class="form-input">
            @foreach($classes as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="publish_now" value="1"> Publish immediately</label>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('announcements.index') }}" class="btn btn-secondary">Cancel</a>
</form>

<script>
document.getElementById('audience-select').addEventListener('change', function() {
    document.getElementById('target-class').style.display = this.value === 'class' ? '' : 'none';
    document.getElementById('target-grade').style.display = this.value === 'grade' ? '' : 'none';
});
</script>
@endsection
