<x-app-layout>
    <x-slot name="title">Assign Homework</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Assign Homework</h1>
    </div>

    <div class="kia-card" style="max-width:760px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('homework.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Section *</label>
                        <select name="section_id" class="form-control" required>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}">{{ $s->schoolClass->name }} – {{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" class="form-control" required>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->name_en }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4" class="form-control"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date *</label>
                    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" required>
                    @error('due_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Attachment (PDF/Word/Image, max 10MB)</label>
                    <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror"
                           accept=".pdf,.doc,.docx,.png,.jpg,.jpeg">
                    @error('attachment')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem">
                        <input type="checkbox" name="publish_now" value="1"> Publish immediately
                    </label>
                </div>
                <div style="display:flex;gap:.75rem">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="{{ route('homework.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
