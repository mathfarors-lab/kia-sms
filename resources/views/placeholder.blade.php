<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ $title }}</h1>
    </div>

    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <h3>{{ $title }} — {{ __('Coming Soon') }}</h3>
                <p>{{ __('This module will be built in an upcoming phase.') }}</p>
                <a href="{{ url()->previous() }}" class="btn btn-ghost">{{ __('Go Back') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
