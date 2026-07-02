<x-app-layout>
    <x-slot name="title">{{ $conversation->subject }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $conversation->subject }}</h1>
            <p class="kia-page-sub">{{ $conversation->participants->pluck('name')->join(' · ') }}</p>
        </div>
        <a href="{{ route('conversations.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="margin-bottom:1rem">
        <div class="kia-card-body" style="display:flex;flex-direction:column;gap:1rem">
            @foreach($messages as $msg)
                <div style="display:flex;{{ $msg->sender_id === auth()->id() ? 'justify-content:flex-end' : '' }}">
                    <div style="max-width:70%;background:{{ $msg->sender_id === auth()->id() ? 'var(--primary)' : 'var(--surface-2)' }};color:{{ $msg->sender_id === auth()->id() ? '#fff' : 'inherit' }};padding:.75rem 1rem;border-radius:.75rem">
                        <div style="font-size:.75rem;margin-bottom:.25rem;opacity:.7">{{ $msg->sender->name }}</div>
                        <div>{{ $msg->body }}</div>
                        <div style="font-size:.7rem;margin-top:.25rem;opacity:.6">{{ $msg->created_at->diffForHumans() }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="kia-card" style="max-width:760px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('conversations.reply', $conversation) }}">
                @csrf
                <div class="form-group">
                    <textarea name="body" rows="3" class="form-control" placeholder="Write a reply…" required></textarea>
                    @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <button class="btn btn-primary" type="submit">Reply</button>
            </form>
        </div>
    </div>
</x-app-layout>
