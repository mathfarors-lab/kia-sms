<x-app-layout>
    <x-slot name="title">{{ __('branches.new_branch') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('owner.branches.index') }}">{{ __('branches.title') }}</a>
        <span class="sep">/</span>
        <span>{{ __('branches.new_branch') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('branches.new_branch') }}</h1>
    </div>

    <div class="kia-card" style="max-width:720px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('owner.branches.store') }}" enctype="multipart/form-data">
                @csrf
                @include('owner.branches._form', ['branch' => null])
                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('branches.new_branch') }}</button>
                    <a href="{{ route('owner.branches.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
