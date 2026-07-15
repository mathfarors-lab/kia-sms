<x-app-layout>
    <x-slot name="title">{{ __('admissions.new_application') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('admissions.index') }}">{{ __('admissions.title') }}</a>
        <span class="sep">/</span>
        <span>{{ __('admissions.new_application') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('admissions.new_application') }}</h1>
    </div>

    <p style="color:var(--muted);font-size:.875rem;max-width:70ch;margin:-8px 0 16px;">{{ __('admissions.relationship_note') }}</p>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('admissions.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admissions._form', ['application' => null])
                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('admissions.new_application') }}</button>
                    <a href="{{ route('admissions.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
