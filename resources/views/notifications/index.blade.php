<x-app-layout>
    <x-slot name="title">{{ __('Notifications') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Notifications') }}</h1>
        </div>
        @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-outline">{{ __('Mark all read') }}</button>
        </form>
        @endif
    </div>

    <div class="kia-card">
        <div class="kia-card-body" style="padding:0">
            @forelse($notifications as $notif)
            <a href="{{ route('notifications.read-go', $notif->id) }}"
               class="kia-notif-item kia-notif-item--row{{ $notif->read_at ? '' : ' unread' }}"
               style="display:flex;gap:14px;align-items:flex-start;padding:14px 20px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;">
                <div style="flex-shrink:0;margin-top:3px;">
                    @if(($notif->data['icon'] ?? '') === 'result')
                    <span style="font-size:1.25rem;">📋</span>
                    @elseif(($notif->data['icon'] ?? '') === 'invoice')
                    <span style="font-size:1.25rem;">🧾</span>
                    @elseif(($notif->data['icon'] ?? '') === 'announcement')
                    <span style="font-size:1.25rem;">📢</span>
                    @else
                    <span style="font-size:1.25rem;">🔔</span>
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:{{ $notif->read_at ? '400' : '600' }};font-size:.875rem;margin-bottom:2px;">
                        {{ $notif->data['title'] ?? __('Notification') }}
                    </div>
                    <div style="font-size:.8125rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $notif->data['body'] ?? $notif->data['message_en'] ?? '' }}
                    </div>
                </div>
                <div style="flex-shrink:0;font-size:.75rem;color:var(--muted);white-space:nowrap;">
                    {{ $notif->created_at->diffForHumans() }}
                </div>
                @if(!$notif->read_at)
                <div style="flex-shrink:0;width:8px;height:8px;border-radius:50%;background:var(--royal);margin-top:5px;"></div>
                @endif
            </a>
            @empty
            <div style="padding:48px;text-align:center;color:var(--muted);">
                {{ __('No notifications yet') }}
            </div>
            @endforelse
        </div>
    </div>

    @if($notifications->hasPages())
    <div style="margin-top:16px;">{{ $notifications->links() }}</div>
    @endif
</x-app-layout>
