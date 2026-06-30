@extends('layouts.app')
@section('title', 'Add Book')
@section('content')
<div class="page-header"><h1>Add Book</h1></div>
<form method="POST" action="{{ route('books.store') }}" class="card form-card" enctype="multipart/form-data">
    @csrf
    <div class="form-group">
        <label>Title *</label>
        <input type="text" name="title" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Author</label>
        <input type="text" name="author" class="form-input">
    </div>
    <div class="form-group">
        <label>ISBN</label>
        <input type="text" name="isbn" class="form-input">
    </div>
    <div class="form-group">
        <label>Category</label>
        <input type="text" name="category" class="form-input">
    </div>
    <div class="form-group">
        <label>Total Copies *</label>
        <input type="number" name="total_copies" class="form-input" value="1" min="1" required>
    </div>
    <div class="form-group">
        <label>Cover Image (PNG/JPG, max 5MB)</label>
        <input type="file" name="cover" class="form-input" accept=".png,.jpg,.jpeg">
        @error('cover')<p class="form-error">{{ $message }}</p>@enderror
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('books.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
