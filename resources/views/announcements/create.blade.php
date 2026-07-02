<x-app-layout>
    <x-slot name="title">New Announcement</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Announcement</h1>
    </div>

    <div class="kia-card" style="max-width:760px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('announcements.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required>
                    @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Body (English) *</label>
                    <textarea name="body_en" rows="5" class="form-control">{{ old('body_en') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Body (Khmer)</label>
                    <textarea name="body_km" rows="5" class="form-control">{{ old('body_km') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Audience *</label>
                    <select name="audience" class="form-control" id="audience-select">
                        <option value="all">All</option>
                        <option value="class">Specific Section</option>
                        <option value="grade">Specific Grade</option>
                    </select>
                </div>
                <div class="form-group" id="target-class" style="display:none">
                    <label class="form-label">Section</label>
                    <select name="target_id" class="form-control">
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}">{{ $s->schoolClass->name }} – {{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="target-grade" style="display:none">
                    <label class="form-label">Grade (Class)</label>
                    <select name="target_id" class="form-control">
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem">
                        <input type="checkbox" name="publish_now" value="1"> Publish immediately
                    </label>
                </div>
                <div style="display:flex;gap:.75rem">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="{{ route('announcements.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('audience-select').addEventListener('change', function() {
        document.getElementById('target-class').style.display = this.value === 'class' ? '' : 'none';
        document.getElementById('target-grade').style.display = this.value === 'grade' ? '' : 'none';
    });
    </script>
</x-app-layout>
