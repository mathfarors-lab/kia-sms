<x-app-layout>
    <x-slot name="title">{{ __('term_results.edit_remark') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('term-results.show', [$academicYear, $semesterSlug, $student]) }}">{{ $student->name_en }}</a>
        <span class="sep">/</span>
        <span>{{ __('term_results.edit_remark') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('term_results.edit_remark') }}</h1>
    </div>

    <div class="kia-card" style="max-width:700px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('term-results.remark.update', [$academicYear, $semesterSlug, $student]) }}">
                @csrf @method('PATCH')
                <div class="form-group">
                    <label class="form-label" for="teacher_remark">{{ __('term_results.teacher_remark') }}</label>
                    <textarea id="teacher_remark" name="teacher_remark" class="form-control" rows="4">{{ old('teacher_remark', $termResult->teacher_remark) }}</textarea>
                    @error('teacher_remark')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                @if($comments->isNotEmpty())
                <div class="form-group">
                    <label class="form-label">{{ __('report_comments.insert_from_bank') }}</label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        @foreach($comments->flatten() as $comment)
                        <button type="button" class="btn btn-sm btn-outline"
                                onclick="insertComment({{ Js::from(app()->getLocale() === 'km' && $comment->text_km ? $comment->text_km : $comment->text_en) }})">
                            {{ \Illuminate\Support\Str::limit($comment->text_en, 40) }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('term-results.show', [$academicYear, $semesterSlug, $student]) }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    function insertComment(text) {
        const field = document.getElementById('teacher_remark');
        field.value = field.value ? field.value.trim() + ' ' + text : text;
        field.focus();
    }
    </script>
    @endpush
</x-app-layout>
