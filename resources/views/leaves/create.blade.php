<x-app-layout>
    <x-slot name="title">Request Leave</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Request Leave</h1>
    </div>

    <div class="kia-card" style="max-width:560px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('leaves.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Leave Type *</label>
                    <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                        <option value="">— Select —</option>
                        <option value="sick" @selected(old('type') === 'sick')>Sick Leave</option>
                        <option value="annual" @selected(old('type') === 'annual')>Annual Leave</option>
                        <option value="unpaid" @selected(old('type') === 'unpaid')>Unpaid Leave</option>
                        <option value="other" @selected(old('type') === 'other')>Other</option>
                    </select>
                    @error('type')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">From *</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}"
                               class="form-control @error('start_date') is-invalid @enderror"
                               min="{{ now()->toDateString() }}" required>
                        @error('start_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">To *</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                               class="form-control @error('end_date') is-invalid @enderror"
                               min="{{ now()->toDateString() }}" required>
                        @error('end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea name="reason" rows="3" class="form-control">{{ old('reason') }}</textarea>
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Submit</button>
                    <a href="{{ route('leaves.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
