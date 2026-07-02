<x-app-layout>
    <x-slot name="title">{{ __('Edit User') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('Edit User') }}</h1>
            <p class="kia-page-sub">{{ $user->email }}</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        <ul style="margin:0;padding-left:1.2em;">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="kia-card" style="max-width:640px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label class="form-label">{{ __('Name') }}</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Email') }}</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Roles') }}</label>
                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:4px;">
                        @foreach($roles as $role)
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="checkbox" name="roles[]" value="{{ $role }}"
                                {{ in_array($role, old('roles', $user->getRoleNames()->toArray())) ? 'checked' : '' }}>
                            <span>{{ ucfirst($role) }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('roles')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Status') }}</label>
                    @if($user->id === auth()->id())
                        <p style="color:var(--muted);font-size:.85rem;">{{ __('You cannot change the status of your own account.') }}</p>
                        <input type="hidden" name="status" value="{{ $user->status }}">
                    @else
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                        <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div style="display:flex;gap:12px;margin-top:24px;">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                    <a href="{{ route('users.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @if($user->student)
    <div class="kia-card" style="max-width:640px;margin-top:16px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Linked Profile') }}</h2>
        </div>
        <div class="kia-card-body">
            <p>{{ __('Student') }}: <strong>{{ $user->student->name_en }}</strong>
                ({{ $user->student->student_code }})</p>
            <p style="color:var(--muted);font-size:.85rem;">{{ __('To remove this link, edit the student profile directly.') }}</p>
        </div>
    </div>
    @elseif($user->staff)
    <div class="kia-card" style="max-width:640px;margin-top:16px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Linked Profile') }}</h2>
        </div>
        <div class="kia-card-body">
            <p>{{ __('Staff member linked to this account.') }}</p>
        </div>
    </div>
    @endif
</x-app-layout>
