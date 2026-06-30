<x-app-layout>
    <x-slot name="title">{{ __('Add Staff') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('staff.index') }}">{{ __('Staff') }}</a>
        <span class="sep">/</span>
        <span>{{ __('Add Staff') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Add Staff Member') }}</h1>
    </div>

    <div class="kia-card" style="max-width:720px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('staff.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="name">{{ __('Full Name') }} <span class="req">*</span></label>
                        <input type="text" id="name" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                               value="{{ old('name') }}" required>
                        @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">{{ __('Email') }} <span class="req">*</span></label>
                        <input type="email" id="email" name="email" class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                               value="{{ old('email') }}" required>
                        @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">{{ __('Phone') }}</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">{{ __('Password') }}</label>
                        <input type="password" id="password" name="password" class="form-control">
                        <small style="color:var(--muted);font-size:.78rem;">{{ __('Leave blank to use "password"') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="role">{{ __('Role') }} <span class="req">*</span></label>
                        <select id="role" name="role" class="form-control {{ $errors->has('role') ? 'is-invalid' : '' }}" required>
                            <option value="">{{ __('Select role…') }}</option>
                            @foreach($roles as $role)
                            <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        @error('role')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="position">{{ __('Position') }}</label>
                        <input type="text" id="position" name="position" class="form-control" value="{{ old('position') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="department">{{ __('Department') }}</label>
                        <input type="text" id="department" name="department" class="form-control" value="{{ old('department') }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="joined_at">{{ __('Join Date') }}</label>
                        <input type="date" id="joined_at" name="joined_at" class="form-control" value="{{ old('joined_at', now()->format('Y-m-d')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="salary">{{ __('Salary (USD)') }}</label>
                        <input type="number" id="salary" name="salary" class="form-control" step="0.01" min="0" value="{{ old('salary') }}">
                    </div>
                </div>

                <div style="display:flex;gap:12px;padding-top:8px;">
                    <button type="submit" class="btn btn-primary">{{ __('Create Staff Member') }}</button>
                    <a href="{{ route('staff.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
