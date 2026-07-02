<x-app-layout>
    <x-slot name="title">Edit Announcement</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Announcement</h1>
    </div>

    <div class="kia-card" style="max-width:760px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('announcements.update', $announcement) }}">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $announcement->title) }}" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Body (English)</label>
                    <textarea name="body_en" rows="5" class="form-control">{{ old('body_en', $announcement->body_en) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Body (Khmer)</label>
                    <textarea name="body_km" rows="5" class="form-control">{{ old('body_km', $announcement->body_km) }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Audience</label>
                    <select name="audience" class="form-control">
                        @foreach(['all','class','grade'] as $opt)
                            <option value="{{ $opt }}" @selected(old('audience', $announcement->audience) === $opt)>{{ ucfirst($opt) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:.75rem">
                    <button class="btn btn-primary" type="submit">Update</button>
                    <a href="{{ route('announcements.show', $announcement) }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
