@php $evaluation = $evaluation ?? null; @endphp

<div class="form-group">
    <label class="form-label" for="evaluation_date">{{ __('staff_evaluations.evaluation_date') }}</label>
    <input type="date" name="evaluation_date" id="evaluation_date" class="form-control {{ $errors->has('evaluation_date') ? 'is-invalid' : '' }}" value="{{ old('evaluation_date', $evaluation?->evaluation_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
    @error('evaluation_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label class="form-label" for="overall_rating">{{ __('staff_evaluations.overall_rating') }} (1–5)</label>
    <input type="number" name="overall_rating" id="overall_rating" class="form-control {{ $errors->has('overall_rating') ? 'is-invalid' : '' }}" min="1" max="5" value="{{ old('overall_rating', $evaluation?->overall_rating ?? 3) }}" required>
    @error('overall_rating')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label class="form-label" for="strengths">{{ __('staff_evaluations.strengths') }}</label>
    <textarea name="strengths" id="strengths" rows="3" class="form-control">{{ old('strengths', $evaluation?->strengths) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label" for="areas_for_improvement">{{ __('staff_evaluations.areas_for_improvement') }}</label>
    <textarea name="areas_for_improvement" id="areas_for_improvement" rows="3" class="form-control">{{ old('areas_for_improvement', $evaluation?->areas_for_improvement) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label" for="comments">{{ __('staff_evaluations.comments') }}</label>
    <textarea name="comments" id="comments" rows="3" class="form-control">{{ old('comments', $evaluation?->comments) }}</textarea>
</div>
