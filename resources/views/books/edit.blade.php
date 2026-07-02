<x-app-layout>
    <x-slot name="title">Edit Book</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Book</h1>
    </div>

    <div class="kia-card" style="max-width:640px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('books.update', $book) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $book->title) }}"
                           class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" value="{{ old('author', $book->author) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" value="{{ old('category', $book->category) }}" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image (leave blank to keep current)</label>
                    <input type="file" name="cover" class="form-control" accept=".png,.jpg,.jpeg">
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Update</button>
                    <a href="{{ route('books.show', $book) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
