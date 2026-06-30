@extends('layouts.app')
@section('title', 'Edit Book')
@section('content')
<div class="page-header"><h1>Edit Book</h1></div>
<form method="POST" action="{{ route('books.update', $book) }}" class="card form-card" enctype="multipart/form-data">
    @csrf @method('PATCH')
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="{{ old('title', $book->title) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Author</label>
        <input type="text" name="author" value="{{ old('author', $book->author) }}" class="form-input">
    </div>
    <div class="form-group">
        <label>Category</label>
        <input type="text" name="category" value="{{ old('category', $book->category) }}" class="form-input">
    </div>
    <div class="form-group">
        <label>Cover Image (leave blank to keep current)</label>
        <input type="file" name="cover" class="form-input" accept=".png,.jpg,.jpeg">
    </div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
