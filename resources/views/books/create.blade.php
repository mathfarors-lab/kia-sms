<x-app-layout>
    <x-slot name="title">Add Book</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Add Book</h1>
    </div>

    <div class="kia-card" style="max-width:640px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}"
                           class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" value="{{ old('author') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" value="{{ old('isbn') }}" class="form-control">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" value="{{ old('category') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Copies *</label>
                        <input type="number" name="total_copies" value="{{ old('total_copies', 1) }}"
                               class="form-control @error('total_copies') is-invalid @enderror" min="1" required>
                        @error('total_copies')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cover Image (PNG/JPG, max 5MB)</label>
                    <input type="file" name="cover" class="form-control @error('cover') is-invalid @enderror"
                           accept=".png,.jpg,.jpeg">
                    @error('cover')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="{{ route('books.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
