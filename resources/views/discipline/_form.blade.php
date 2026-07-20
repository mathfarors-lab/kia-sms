@php $incident = $incident ?? null; @endphp

<div class="kia-form-group">
    <label class="kia-label">{{ __('discipline_records.incident_date') }} *</label>
    <input type="date" name="incident_date" value="{{ old('incident_date', $incident?->incident_date?->toDateString() ?? now()->toDateString()) }}" class="kia-input @error('incident_date') is-invalid @enderror">
    @error('incident_date')<span class="kia-field-error">{{ $message }}</span>@enderror
</div>

<div class="kia-form-group">
    <label class="kia-label">{{ __('discipline_records.type') }} *</label>
    <select name="type" class="kia-select @error('type') is-invalid @enderror">
        <option value="">—</option>
        @foreach(\App\Models\DisciplineIncident::TYPES as $t)
        <option value="{{ $t }}" @selected(old('type', $incident?->type) === $t)>{{ __('discipline_records.type_'.$t) }}</option>
        @endforeach
    </select>
    @error('type')<span class="kia-field-error">{{ $message }}</span>@enderror
</div>

<div class="kia-form-group">
    <label class="kia-label">{{ __('discipline_records.description') }} *</label>
    <textarea name="description" rows="4" class="kia-input @error('description') is-invalid @enderror">{{ old('description', $incident?->description) }}</textarea>
    @error('description')<span class="kia-field-error">{{ $message }}</span>@enderror
</div>

<div class="kia-form-group">
    <label class="kia-label">{{ __('discipline_records.action_taken') }}</label>
    <textarea name="action_taken" rows="3" class="kia-input @error('action_taken') is-invalid @enderror">{{ old('action_taken', $incident?->action_taken) }}</textarea>
    @error('action_taken')<span class="kia-field-error">{{ $message }}</span>@enderror
</div>
