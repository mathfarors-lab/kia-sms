<x-app-layout>
    <x-slot name="title">{{ $classSubject->subject->name_en }} — {{ $classSubject->schoolClass->name }}</x-slot>

    @php
        $user = auth()->user();
        $canManage = $user->can('curriculum.manage');
        $canToggle = $canManage || ($user->staff && $classSubject->teacher_id === $user->staff->id);
    @endphp

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $classSubject->subject->name_en }}</h1>
            <p class="kia-page-sub">{{ $classSubject->schoolClass->name }} &middot; {{ $classSubject->teacher?->user?->name ?? '—' }}</p>
        </div>
        <a href="{{ route('curriculum.for-class', $classSubject->schoolClass) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div class="kia-card">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('curriculum.topics') }}</h2></div>
        <div class="kia-card-body">
            @if($canManage)
            <form method="POST" action="{{ route('curriculum-topics.store', $classSubject) }}"
                  style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
                @csrf
                <div class="form-group" style="flex:1;min-width:180px;margin-bottom:0;">
                    <label class="form-label" for="topic-title">{{ __('curriculum.topic_title') }}</label>
                    <input type="text" id="topic-title" name="title" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}" maxlength="200" required>
                    @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="flex:2;min-width:220px;margin-bottom:0;">
                    <label class="form-label" for="topic-description">{{ __('curriculum.description') }}</label>
                    <input type="text" id="topic-description" name="description" class="form-control" maxlength="2000">
                </div>
                <div class="form-group" style="width:90px;margin-bottom:0;">
                    <label class="form-label" for="topic-sequence">#</label>
                    <input type="number" id="topic-sequence" name="sequence" class="form-control" min="0" value="{{ $classSubject->curriculumTopics->count() }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('curriculum.add_topic') }}</button>
            </form>
            @endif

            @forelse($classSubject->curriculumTopics as $topic)
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
                <div>
                    <div style="font-weight:600;font-size:.875rem;{{ $topic->is_completed ? 'text-decoration:line-through;color:var(--muted);' : '' }}">
                        {{ $topic->title }}
                    </div>
                    @if($topic->description)
                    <div style="font-size:.8rem;color:var(--muted);margin-top:2px;">{{ $topic->description }}</div>
                    @endif
                    <span class="pill {{ $topic->is_completed ? 'pill-ok' : 'pill-muted' }}" style="margin-top:6px;display:inline-block;">
                        {{ $topic->is_completed ? __('curriculum.completed') : __('curriculum.not_started') }}
                    </span>
                </div>
                <div style="display:flex;gap:.4rem;flex-shrink:0;">
                    @if($canToggle)
                    <form method="POST" action="{{ route('curriculum-topics.toggle', $topic) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline">
                            {{ $topic->is_completed ? __('curriculum.mark_incomplete') : __('curriculum.mark_complete') }}
                        </button>
                    </form>
                    @endif
                    @if($canManage)
                    <a href="{{ route('curriculum-topics.edit', $topic) }}" class="btn btn-sm btn-ghost">{{ __('Edit') }}</a>
                    <form method="POST" action="{{ route('curriculum-topics.destroy', $topic) }}" onsubmit="return confirm('{{ __('curriculum.confirm_delete_topic') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <p style="color:var(--muted);font-size:.875rem;">{{ __('curriculum.no_topics_yet') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
