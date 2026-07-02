<x-app-layout>
    <x-slot name="title">{{ __('Library Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Library Dashboard') }}</h1>
    </div>
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('Catalogue, issues, and returns') }}</h3>
                <a href="{{ route('books.index') }}" class="btn btn-primary mt-3">{{ __('Manage Books') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
