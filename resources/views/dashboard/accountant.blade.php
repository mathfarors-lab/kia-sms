<x-app-layout>
    <x-slot name="title">{{ __('Finance Dashboard') }}</x-slot>
    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Finance Dashboard') }}</h1>
    </div>
    <div class="kia-card">
        <div class="kia-card-body">
            <div class="kia-empty">
                <h3>{{ __('Invoices, payments, and collection reports') }}</h3>
                <a href="{{ route('finance.dashboard') }}" class="btn btn-primary mt-3">{{ __('Go to Finance Dashboard') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
