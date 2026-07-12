<x-app-layout>
    <x-slot name="title">{{ __('branches.admins_of', ['name' => $branch->name_en]) }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('owner.branches.index') }}">{{ __('branches.title') }}</a>
        <span class="sep">/</span>
        <a href="{{ route('owner.branches.edit', $branch) }}">{{ $branch->name_en }}</a>
        <span class="sep">/</span>
        <span>{{ __('branches.manage_admins') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('branches.admins_of', ['name' => $branch->name_en]) }}</h1>
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="kia-alert" style="background:var(--danger-light,#fee2e2);color:var(--danger,#991b1b)">{{ $errors->first() }}</div>@endif

    <div class="kia-card" style="margin-bottom:20px;max-width:640px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('branches.manage_admins') }}</h2></div>
        <div class="kia-card-body">
            @forelse($admins as $admin)
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--line);">
                <div>
                    <div style="font-weight:600;">{{ $admin->name }}</div>
                    <div style="font-size:.8rem;color:var(--muted);">{{ $admin->email }}</div>
                </div>
                <form method="POST" action="{{ route('owner.branches.admins.remove', [$branch, $admin]) }}"
                      onsubmit="return confirm('{{ __('branches.remove_admin_confirm', ['name' => $admin->name]) }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-ghost" style="color:var(--danger, #c0392b);">{{ __('branches.remove_admin') }}</button>
                </form>
            </div>
            @empty
            <div class="kia-empty"><h3>{{ __('branches.no_admins') }}</h3></div>
            @endforelse
        </div>
    </div>

    <div class="kia-card" style="max-width:640px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('branches.appoint_admin') }}</h2></div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('owner.branches.admins.appoint', $branch) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="existing_email">{{ __('branches.appoint_existing') }}</label>
                    <input type="email" id="existing_email" name="existing_email" class="form-control {{ $errors->has('existing_email') ? 'is-invalid' : '' }}"
                           value="{{ old('existing_email') }}" placeholder="name@kia.edu.kh">
                    <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('branches.appoint_existing_hint') }}</div>
                    @error('existing_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div style="display:flex;align-items:center;gap:12px;margin:1.25rem 0;color:var(--muted);font-size:.8rem;">
                    <div style="flex:1;height:1px;background:var(--line);"></div>
                    {{ __('branches.or_create_new') }}
                    <div style="flex:1;height:1px;background:var(--line);"></div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="new_name">{{ __('Full Name') }}</label>
                        <input type="text" id="new_name" name="new_name" class="form-control {{ $errors->has('new_name') ? 'is-invalid' : '' }}"
                               value="{{ old('new_name') }}">
                        @error('new_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_email">{{ __('Email') }}</label>
                        <input type="email" id="new_email" name="new_email" class="form-control {{ $errors->has('new_email') ? 'is-invalid' : '' }}"
                               value="{{ old('new_email') }}">
                        @error('new_email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="new_password">{{ __('Password') }}</label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                        <div style="font-size:.75rem;color:var(--muted);margin-top:4px;">{{ __('Leave blank to use "password"') }}</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top:.5rem;">{{ __('branches.appoint_admin') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
