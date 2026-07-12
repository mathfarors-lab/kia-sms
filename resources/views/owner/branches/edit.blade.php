<x-app-layout>
    <x-slot name="title">{{ __('branches.edit_branch') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('owner.branches.index') }}">{{ __('branches.title') }}</a>
        <span class="sep">/</span>
        <span>{{ $branch->name_en }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('branches.edit_branch') }}</h1>
        <a href="{{ route('owner.branches.admins', $branch) }}" class="btn btn-outline">{{ __('branches.manage_admins') }}</a>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div class="kia-card" style="max-width:720px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('owner.branches.update', $branch) }}" enctype="multipart/form-data">
                @csrf @method('PATCH')
                @include('owner.branches._form', ['branch' => $branch])
                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('owner.branches.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
