<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — KIA School</title>
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
                {{-- Locale switcher --}}
                <form method="POST" action="{{ route('locale.switch') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'en' ? 'km' : 'en' }}">
                    <button type="submit" class="kia-topbar-btn" title="{{ __('Switch language') }}">
                        <span style="font-size:.75rem;font-weight:700;">{{ strtoupper(app()->getLocale()) }}</span>
                    </button>
                </form>

                {{-- Notifications --}}
                <button class="kia-topbar-btn" title="{{ __('Notifications') }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </button>

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
