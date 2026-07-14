<x-app-layout>
    <x-slot name="title">{{ __('report_comments.title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('report_comments.title') }}</h1>
            <p class="kia-page-sub">{{ __('report_comments.subtitle') }}</p>
        </div>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ $errors->first() }}</div>@endif

    <div class="kia-card" style="margin-bottom:16px;max-width:640px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('report_comments.add_new') }}</h2></div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('report-comments.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="category">{{ __('report_comments.category') }}</label>
                        <input type="text" id="category" name="category" class="form-control" value="{{ old('category') }}" placeholder="{{ __('report_comments.category_placeholder') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="text_en">{{ __('report_comments.text_en') }} <span class="req">*</span></label>
                    <textarea id="text_en" name="text_en" class="form-control" rows="2" required>{{ old('text_en') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label" for="text_km">{{ __('report_comments.text_km') }}</label>
                    <textarea id="text_km" name="text_km" class="form-control" rows="2">{{ old('text_km') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('report_comments.add_new') }}</button>
            </form>
        </div>
    </div>

    @forelse($comments as $category => $group)
    <div class="kia-card" style="margin-bottom:16px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ $category ?: __('report_comments.uncategorized') }}</h2></div>
        <div class="kia-card-body">
            @foreach($group as $comment)
            <div style="padding:10px 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                <div>
                    <div>{{ $comment->text_en }}</div>
                    @if($comment->text_km)<div style="color:var(--muted);font-size:.85rem;margin-top:2px;">{{ $comment->text_km }}</div>@endif
                </div>
                <form method="POST" action="{{ route('report-comments.destroy', $comment) }}" onsubmit="return confirm('{{ __('Delete') }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--danger,#c0392b);">{{ __('Delete') }}</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="kia-card"><div class="kia-card-body"><div class="kia-empty"><h3>{{ __('report_comments.none_yet') }}</h3></div></div></div>
    @endforelse
</x-app-layout>
