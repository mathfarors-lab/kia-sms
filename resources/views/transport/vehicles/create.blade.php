<x-app-layout>
    <x-slot name="title">Add Vehicle</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Add Vehicle to {{ $route->name }}</h1>
    </div>

    <div class="kia-card" style="max-width:560px">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('transport.vehicles.store', $route) }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Plate No *</label>
                        <input type="text" name="plate_no" value="{{ old('plate_no') }}"
                               class="form-control @error('plate_no') is-invalid @enderror" required>
                        @error('plate_no')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacity (seats) *</label>
                        <input type="number" name="capacity" value="{{ old('capacity') }}"
                               class="form-control @error('capacity') is-invalid @enderror" min="1" required>
                        @error('capacity')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Driver Name *</label>
                        <input type="text" name="driver_name" value="{{ old('driver_name') }}"
                               class="form-control @error('driver_name') is-invalid @enderror" required>
                        @error('driver_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driver Phone</label>
                        <input type="text" name="driver_phone" value="{{ old('driver_phone') }}" class="form-control">
                    </div>
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1rem">
                    <button class="btn btn-primary" type="submit">Save</button>
                    <a href="{{ route('transport.routes.index') }}" class="btn btn-ghost">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
