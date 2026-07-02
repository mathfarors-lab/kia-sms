<x-app-layout>
    <x-slot name="title">New Message</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Message</h1>
    </div>

    <div class="kia-card" style="max-width:640px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                @if($recipient)
                    <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                    <div class="form-group">
                        <label class="form-label">To</label>
                        <p class="form-control" style="background:var(--surface-2)">{{ $recipient->name }}</p>
                    </div>
                @else
                    <div class="form-group">
                        <label class="form-label">Recipient ID</label>
                        <input type="number" name="recipient_id" class="form-control @error('recipient_id') is-invalid @enderror" required>
                        @error('recipient_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Subject *</label>
                    <input type="text" name="subject" class="form-control @error('subject') is-invalid @enderror" required>
                    @error('subject')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Message *</label>
                    <textarea name="body" rows="5" class="form-control @error('body') is-invalid @enderror" required></textarea>
                    @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div style="display:flex;gap:.75rem">
                    <button class="btn btn-primary" type="submit">Send</button>
                    <a href="{{ route('conversations.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
