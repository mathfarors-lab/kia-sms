<x-app-layout>
    <x-slot name="title">Edit Academic Year</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">Edit Academic Year</h1>
        <a href="{{ route('academic-years.index') }}" class="btn btn-ghost">Back</a>
    </div>

    <div class="kia-card" style="max-width:560px">
        <form method="POST" action="{{ route('academic-years.update', $academicYear) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $academicYear->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $academicYear->start_date->toDateString()) }}" required>
                @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $academicYear->end_date->toDateString()) }}" required>
                @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $academicYear->is_active) ? 'checked' : '' }}>
                    Set as active year
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('academic-years.index') }}" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>

    <div class="kia-card" style="max-width:560px;margin-top:20px;">
        <div class="kia-card-header"><h2 class="kia-card-title">{{ __('semester_planning.section_title') }}</h2></div>
        <div class="kia-card-body">
            <form method="POST" action="{{ route('semesters.store', $academicYear) }}"
                  style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--line);">
                @csrf
                <div class="form-group" style="width:140px;margin-bottom:0;">
                    <label class="form-label" for="sem-number">{{ __('semester_planning.semester_number') }}</label>
                    <select id="sem-number" name="semester_number" class="form-control {{ $errors->has('semester_number') ? 'is-invalid' : '' }}">
                        <option value="1">{{ __('semester_planning.semester_1') }}</option>
                        <option value="2">{{ __('semester_planning.semester_2') }}</option>
                    </select>
                    @error('semester_number')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="flex:1;min-width:140px;margin-bottom:0;">
                    <label class="form-label" for="sem-name">{{ __('semester_planning.name_optional') }}</label>
                    <input type="text" id="sem-name" name="name" class="form-control" maxlength="100">
                </div>
                <div class="form-group" style="width:150px;margin-bottom:0;">
                    <label class="form-label" for="sem-start">{{ __('semester_planning.start_date') }}</label>
                    <input type="date" id="sem-start" name="start_date" class="form-control {{ $errors->has('start_date') ? 'is-invalid' : '' }}" required>
                    @error('start_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group" style="width:150px;margin-bottom:0;">
                    <label class="form-label" for="sem-end">{{ __('semester_planning.end_date') }}</label>
                    <input type="date" id="sem-end" name="end_date" class="form-control {{ $errors->has('end_date') ? 'is-invalid' : '' }}" required>
                    @error('end_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('semester_planning.add_semester') }}</button>
            </form>

            @forelse($academicYear->semesters as $semester)
            <div style="display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid var(--line);flex-wrap:wrap;">
                <div>
                    <div style="font-weight:600;font-size:.875rem;">{{ $semester->displayName() }}</div>
                    <div style="font-size:.78rem;color:var(--muted);">
                        {{ $semester->start_date->format('d M Y') }} &ndash; {{ $semester->end_date->format('d M Y') }}
                    </div>
                </div>
                <form method="POST" action="{{ route('semesters.destroy', $semester) }}" onsubmit="return confirm('{{ __('semester_planning.confirm_delete') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                </form>
            </div>
            @empty
            <p style="color:var(--muted);font-size:.875rem;">{{ __('semester_planning.no_semesters_yet') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
