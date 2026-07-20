<x-app-layout>
    <x-slot name="title">{{ $feedback->subject }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $feedback->subject }}</h1>
            <p class="kia-page-sub">
                {{ __('feedback.category_' . $feedback->category) }} &middot;
                {{ __('feedback.submitted_by') }} {{ $feedback->submitter->name ?? '—' }} &middot;
                {{ $feedback->created_at->format('d M Y') }}
                @if($feedback->student) &middot; {{ $feedback->student->name_km ?: $feedback->student->name_en }} @endif
            </p>
        </div>
        <a href="{{ route('feedback.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>@endif

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">
        <div>
            <div class="kia-card" style="margin-bottom:20px;">
                <div class="kia-card-body" style="display:flex;flex-direction:column;gap:1rem;">
                    <div style="display:flex;justify-content:flex-start;">
                        <div style="max-width:100%;background:var(--surface-2);border-radius:.75rem;padding:.75rem 1rem;">
                            <div style="font-size:.75rem;opacity:.7;">{{ $feedback->submitter->name ?? '—' }}</div>
                            <div style="white-space:pre-line;">{{ $feedback->body }}</div>
                            <div style="font-size:.7rem;opacity:.6;">{{ $feedback->created_at->diffForHumans() }}</div>
                        </div>
                    </div>

                    @if($feedback->attachment_path)
                    <div><a href="{{ route('feedback.attachment', $feedback) }}" class="btn btn-sm btn-outline">{{ __('feedback.download_attachment') }}</a></div>
                    @endif

                    @forelse($feedback->replies as $reply)
                    @php $mine = $reply->user_id === auth()->id(); @endphp
                    <div style="display:flex;justify-content:{{ $mine ? 'flex-end' : 'flex-start' }};">
                        <div style="max-width:80%;background:{{ $mine ? 'var(--primary)' : 'var(--surface-2)' }};color:{{ $mine ? '#fff' : 'inherit' }};border-radius:.75rem;padding:.75rem 1rem;">
                            <div style="font-size:.75rem;opacity:.7;">{{ $reply->user->name ?? '—' }}</div>
                            <div style="white-space:pre-line;">{{ $reply->body }}</div>
                            <div style="font-size:.7rem;opacity:.6;">{{ $reply->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @empty
                    <p style="color:var(--muted);font-size:.875rem;">{{ __('feedback.no_replies_yet') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="kia-card">
                <div class="kia-card-body">
                    <form method="POST" action="{{ route('feedback.reply', $feedback) }}">
                        @csrf
                        <div class="form-group">
                            <textarea name="body" rows="3" class="form-control {{ $errors->has('body') ? 'is-invalid' : '' }}" placeholder="{{ __('feedback.reply_placeholder') }}" required>{{ old('body') }}</textarea>
                            @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('feedback.post_reply') }}</button>
                    </form>
                </div>
            </div>
        </div>

        <div>
            <div class="kia-card">
                <div class="kia-card-header"><h2 class="kia-card-title">{{ __('feedback.status') }}</h2></div>
                <div class="kia-card-body">
                    @php $colors = ['open' => 'pill-warn', 'in_progress' => 'pill-royal', 'resolved' => 'pill-ok', 'closed' => 'pill-muted']; @endphp
                    <span class="pill {{ $colors[$feedback->status] ?? 'pill-muted' }}">{{ __('feedback.status_' . $feedback->status) }}</span>

                    @can('feedback.manage')
                    <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem;">
                        @foreach(\App\Models\FeedbackItem::TRANSITIONS[$feedback->status] ?? [] as $next)
                        <form method="POST" action="{{ route('feedback.status', $feedback) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $next }}">
                            <button type="submit" class="btn btn-outline btn-sm" style="width:100%;">{{ __('feedback.status_' . $next) }}</button>
                        </form>
                        @endforeach

                        @if($feedback->canReopen())
                        <form method="POST" action="{{ route('feedback.reopen', $feedback) }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">{{ __('feedback.reopen') }}</button>
                        </form>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('feedback.assign', $feedback) }}" style="margin-top:1rem;">
                        @csrf
                        <label class="form-label">{{ __('feedback.assigned_to') }}</label>
                        <select name="assigned_to" class="form-control" onchange="this.form.submit()">
                            <option value="">{{ __('feedback.unassigned') }}</option>
                            @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}" {{ (string) $feedback->assigned_to === (string) $staffUser->id ? 'selected' : '' }}>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
