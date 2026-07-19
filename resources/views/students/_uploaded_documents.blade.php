<div class="kia-card" style="margin-top:20px;">
    <div class="kia-card-header"><h2 class="kia-card-title">{{ __('Uploaded Documents') }}</h2></div>
    <div class="kia-card-body">
        @can('students.edit')
        <form method="POST" action="{{ route('student-documents.store', $student) }}" enctype="multipart/form-data"
              style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
            @csrf
            <div class="form-group" style="flex:1;min-width:160px;margin-bottom:0;">
                <label class="form-label" for="doc-label">{{ __('Label') }}</label>
                <input type="text" id="doc-label" name="label" class="form-control {{ $errors->has('label') ? 'is-invalid' : '' }}"
                       maxlength="150" required placeholder="{{ __('e.g. Birth Certificate') }}">
                @error('label')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0;">
                <label class="form-label" for="doc-file">{{ __('File') }}</label>
                <input type="file" id="doc-file" name="file" class="form-control {{ $errors->has('file') ? 'is-invalid' : '' }}"
                       accept=".pdf,.jpg,.jpeg,.png" required>
                @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="btn btn-primary">{{ __('Upload') }}</button>
        </form>
        @endcan

        @forelse($student->documents as $doc)
        <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
            <div>
                <div style="font-weight:600;font-size:.875rem;">{{ $doc->label }}</div>
                <div style="font-size:.78rem;color:var(--muted);">
                    {{ $doc->original_name }} &middot; {{ $doc->created_at->format('d M Y') }}
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('student-documents.download', $doc) }}" class="btn btn-sm btn-outline">{{ __('Download') }}</a>
                @can('students.edit')
                <form method="POST" action="{{ route('student-documents.destroy', $doc) }}" onsubmit="return confirm('{{ __('Delete this document?') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                </form>
                @endcan
            </div>
        </div>
        @empty
        <p style="color:var(--muted);font-size:.875rem;">{{ __('No documents uploaded yet.') }}</p>
        @endforelse
    </div>
</div>
