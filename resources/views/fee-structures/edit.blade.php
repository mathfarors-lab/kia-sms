<x-app-layout>
    <x-slot name="title">Edit Fee Structure</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Edit — {{ $feeStructure->name }}</h1></div>
        <a href="{{ route('fee-structures.index') }}" class="btn btn-ghost">← Back</a>
    </div>

    <div class="kia-card" style="max-width:520px">
        <form method="POST" action="{{ route('fee-structures.update', $feeStructure) }}" class="kia-form">
            @csrf @method('PUT')
            <div class="kia-form-group">
                <label class="kia-label">Fee Name *</label>
                <input name="name" value="{{ old('name', $feeStructure->name) }}" class="kia-input @error('name') is-invalid @enderror">
                @error('name')<span class="kia-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="kia-form-row kia-form-row--2">
                <div class="kia-form-group">
                    <label class="kia-label">Amount (USD) *</label>
                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $feeStructure->amount) }}" class="kia-input @error('amount') is-invalid @enderror">
                    @error('amount')<span class="kia-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="kia-form-group">
                    <label class="kia-label">Frequency *</label>
                    <select name="frequency" class="kia-select">
                        @foreach(['once','monthly','term','annual'] as $f)
                            <option value="{{ $f }}" @selected(old('frequency', $feeStructure->frequency) === $f)>{{ ucfirst($f) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="kia-form-group">
                <label class="kia-label">Applies To Class</label>
                <select name="school_class_id" class="kia-select">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('school_class_id', $feeStructure->school_class_id) == $class->id)>{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="kia-form-group">
                <label class="kia-checkbox-label">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $feeStructure->is_active))> Active
                </label>
            </div>
            <div class="kia-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('fee-structures.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
