<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ __('KIA School System') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/kia.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<div class="kia-shell">

    {{-- Sidebar --}}
    @include('layouts.sidebar')

    {{-- Mobile overlay --}}
    <div class="kia-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    {{-- Main area --}}
    <div class="kia-main">

        {{-- Top bar --}}
        <header class="kia-topbar">
            <button class="kia-mobile-toggle" onclick="toggleSidebar()" aria-label="Menu">
                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            <div class="kia-topbar-search">
                <svg class="kia-topbar-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="search" placeholder="{{ __('Search...') }}" id="globalSearch">
            </div>

            <div class="kia-topbar-actions">
                {{-- Owner branch switcher (M1 multi-branch) --}}
                @role('owner')
                @php
                    $ownerBranches = \App\Models\Branch::where('is_active', true)->orderBy('id')->get(['id', 'name_en']);
                    $currentBranchId = session('current_branch_id');
                @endphp
                <form method="POST" action="{{ route('branch.switch') }}" style="display:inline">
                    @csrf
                    <select name="branch_id" onchange="this.form.submit()" class="kia-topbar-btn"
                            title="{{ __('Switch branch') }}"
                            style="font-size:.78rem;font-weight:600;max-width:160px;">
                        @foreach($ownerBranches as $b)
                        <option value="{{ $b->id }}" @selected($currentBranchId == $b->id)>{{ $b->name_en }}</option>
                        @endforeach
                    </select>
                </form>
                @endrole

                {{-- Locale switcher --}}
                <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'en' ? 'km' : 'en' }}">
                    <button type="submit" class="kia-topbar-btn" title="{{ __('Switch language') }}">
                        <span style="font-size:.75rem;font-weight:700;">{{ strtoupper(app()->getLocale()) }}</span>
                    </button>
                </form>

                {{-- Notifications bell --}}
                @php
                    $notifUnreadCount = auth()->user()->unreadNotifications()->count();
                    $notifRecent      = auth()->user()->notifications()->latest()->take(5)->get();
                @endphp
                <div class="kia-dropdown kia-notif-dropdown" id="notifDropdown">
                    <button class="kia-topbar-btn kia-notif-trigger" onclick="toggleDropdown('notifDropdown')" title="{{ __('Notifications') }}">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        @if($notifUnreadCount > 0)
                        <span class="kia-notif-badge">{{ $notifUnreadCount > 99 ? '99+' : $notifUnreadCount }}</span>
                        @endif
                    </button>
                    <div class="kia-dropdown-menu kia-notif-panel">
                        {{-- Header --}}
                        <div class="kia-notif-header">
                            <span style="font-weight:600;font-size:.875rem;">{{ __('Notifications') }}</span>
                            @if($notifUnreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}" style="margin:0">
                                @csrf
                                <button type="submit" class="kia-notif-read-all">{{ __('Mark all read') }}</button>
                            </form>
                            @endif
                        </div>
                        {{-- Recent list --}}
                        @forelse($notifRecent as $notif)
                        <a href="{{ route('notifications.read-go', $notif->id) }}"
                           class="kia-notif-item{{ $notif->read_at ? '' : ' unread' }}">
                            <div class="kia-notif-title">{{ $notif->data['title'] ?? __('Notification') }}</div>
                            <div class="kia-notif-body">{{ \Illuminate\Support\Str::limit($notif->data['body'] ?? $notif->data['message_en'] ?? '', 70) }}</div>
                            <div class="kia-notif-time">{{ $notif->created_at->diffForHumans() }}</div>
                        </a>
                        @empty
                        <div class="kia-notif-empty">{{ __('No notifications yet') }}</div>
                        @endforelse
                        {{-- Footer link --}}
                        <a href="{{ route('notifications.index') }}" class="kia-notif-footer">{{ __('View all notifications') }}</a>
                    </div>
                </div>

                {{-- User avatar + dropdown --}}
                <div class="kia-dropdown" id="userDropdown">
                    <div class="kia-avatar" onclick="toggleDropdown('userDropdown')" title="{{ auth()->user()->name }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="kia-dropdown-menu">
                        <div style="padding:12px 16px 8px;border-bottom:1px solid var(--line)">
                            <div style="font-weight:600;font-size:.875rem;">{{ auth()->user()->name }}</div>
                            <div style="font-size:.75rem;color:var(--muted);">{{ auth()->user()->email }}</div>
                            @if(auth()->user()->getRoleNames()->first())
                            <span class="pill pill-royal mt-1">{{ auth()->user()->getRoleNames()->first() }}</span>
                            @endif
                        </div>
                        <a href="{{ route('profile.edit') }}" class="kia-dropdown-item">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            {{ __('My Profile') }}
                        </a>
                        <div class="kia-dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="kia-dropdown-item danger" style="width:100%;background:none;border:none;text-align:left;cursor:pointer;">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                {{ __('Sign out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success') || session('error'))
        <div style="padding:16px 28px 0;">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
        </div>
        @endif

        {{-- Page content --}}
        <div class="kia-page">
            {{ $slot }}
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.kia-sidebar').classList.toggle('open');
}
function closeSidebar() {
    document.querySelector('.kia-sidebar').classList.remove('open');
}
function toggleDropdown(id) {
    document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', function(e) {
    document.querySelectorAll('.kia-dropdown.open').forEach(function(el) {
        if (!el.contains(e.target)) el.classList.remove('open');
    });
});
</script>
@stack('scripts')
</body>
</html>
