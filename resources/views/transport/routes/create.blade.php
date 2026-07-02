<x-app-layout>
    <x-slot name="title">New Transport Route</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">New Transport Route</h1>
    </div>

    <div class="kia-card" style="max-width:560px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('transport.routes.store') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Route Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly Fare (USD)</label>
                    <input type="number" name="fare" value="{{ old('fare', '0.00') }}"
                           class="form-control @error('fare') is-invalid @enderror" step="0.01" min="0">
                    @error('fare')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="{{ route('transport.routes.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
