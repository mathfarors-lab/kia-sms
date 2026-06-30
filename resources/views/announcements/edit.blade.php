@extends('layouts.app')
@section('title', 'Edit Announcement')
@section('content')
<div class="page-header"><h1>Edit Announcement</h1></div>

<form method="POST" action="{{ route('announcements.update', $announcement) }}" class="card form-card">
    @csrf @method('PUT')
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Body (English)</label>
        <textarea name="body_en" rows="5" class="form-input">{{ old('body_en', $announcement->body_en) }}</textarea>
    </div>
    <div class="form-group">
        <label>Body (Khmer)</label>
        <textarea name="body_km" rows="5" class="form-input">{{ old('body_km', $announcement->body_km) }}</textarea>
    </div>
    <div class="form-group">
        <label>Audience</label>
        <select name="audience" class="form-input">
            @foreach(['all','class','grade'] as $opt)
                <option value="{{ $opt }}" @selected(old('audience', $announcement->audience) === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a href="{{ route('announcements.show', $announcement) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
