<x-app-layout>
    <x-slot name="title">Issue Book</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Issue: {{ $book->title }}</h1>
    </div>

    <div class="kia-card" style="max-width:480px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('books.issue.store', $book) }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control @error('student_id') is-invalid @enderror" required>
                        <option value="">— Select student —</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}">{{ $s->name_en }} ({{ $s->student_code }})</option>
                        @endforeach
                    </select>
                    @error('student_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date *</label>
                    <input type="date" name="due_date"
                           value="{{ old('due_date', now()->addDays(14)->format('Y-m-d')) }}"
                           class="form-control @error('due_date') is-invalid @enderror"
                           min="{{ now()->addDay()->format('Y-m-d') }}" required>
                    @error('due_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Issue</button>
                    <a href="{{ route('books.show', $book) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
