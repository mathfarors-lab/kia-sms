@php
    $survey = $survey ?? null;
    $existingQuestions = $survey?->questions ?? collect();
@endphp

<div class="form-group">
    <label class="form-label" for="title_en">{{ __('surveys.title_en') }}</label>
    <input type="text" name="title_en" id="title_en" class="form-control {{ $errors->has('title_en') ? 'is-invalid' : '' }}" value="{{ old('title_en', $survey?->title_en) }}" maxlength="150" required>
    @error('title_en')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>

<div class="form-group">
    <label class="form-label" for="title_km">{{ __('surveys.title_km') }}</label>
    <input type="text" name="title_km" id="title_km" class="form-control" value="{{ old('title_km', $survey?->title_km) }}" maxlength="150">
</div>

<div class="form-group">
    <label class="form-label" for="description_en">{{ __('surveys.description_en') }}</label>
    <textarea name="description_en" id="description_en" rows="3" class="form-control">{{ old('description_en', $survey?->description_en) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label" for="description_km">{{ __('surveys.description_km') }}</label>
    <textarea name="description_km" id="description_km" rows="3" class="form-control">{{ old('description_km', $survey?->description_km) }}</textarea>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <div class="form-group">
        <label class="form-label" for="audience">{{ __('surveys.audience') }}</label>
        <select name="audience" id="audience" class="form-control {{ $errors->has('audience') ? 'is-invalid' : '' }}" onchange="kiaSurveyToggleAudience()" required>
            @foreach(\App\Models\Survey::AUDIENCES as $a)
            <option value="{{ $a }}" {{ old('audience', $survey?->audience) === $a ? 'selected' : '' }}>{{ __('surveys.audience_' . $a) }}</option>
            @endforeach
        </select>
        @error('audience')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>

    <div class="form-group" id="kia-target-role" style="display:none">
        <label class="form-label">{{ __('surveys.audience_role') }}</label>
        <select name="target_id" class="form-control kia-target-select">
            @foreach($roles as $r)
            <option value="{{ $r->id }}" {{ (string) old('target_id', $survey?->target_id) === (string) $r->id ? 'selected' : '' }}>{{ ucfirst($r->name) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" id="kia-target-branch" style="display:none">
        <label class="form-label">{{ __('surveys.audience_branch') }}</label>
        <select name="target_id" class="form-control kia-target-select">
            @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ (string) old('target_id', $survey?->target_id) === (string) $b->id ? 'selected' : '' }}>{{ $b->name_km ?: $b->name_en }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" id="kia-target-class" style="display:none">
        <label class="form-label">{{ __('surveys.audience_class') }}</label>
        <select name="target_id" class="form-control kia-target-select">
            @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ (string) old('target_id', $survey?->target_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" id="kia-target-section" style="display:none">
        <label class="form-label">{{ __('surveys.audience_section') }}</label>
        <select name="target_id" class="form-control kia-target-select">
            @foreach($sections as $s)
            <option value="{{ $s->id }}" {{ (string) old('target_id', $survey?->target_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->schoolClass->name ?? '' }} – {{ $s->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group">
    <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;">
        <input type="checkbox" name="is_anonymous" value="1" {{ old('is_anonymous', $survey?->is_anonymous) ? 'checked' : '' }}>
        {{ __('surveys.is_anonymous') }}
    </label>
    <p style="color:var(--muted);font-size:.8rem;margin-top:.25rem;">{{ __('surveys.is_anonymous_note') }}</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <div class="form-group">
        <label class="form-label" for="opens_at">{{ __('surveys.opens_at') }}</label>
        <input type="datetime-local" name="opens_at" id="opens_at" class="form-control" value="{{ old('opens_at', $survey?->opens_at?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="form-group">
        <label class="form-label" for="closes_at">{{ __('surveys.closes_at') }}</label>
        <input type="datetime-local" name="closes_at" id="closes_at" class="form-control {{ $errors->has('closes_at') ? 'is-invalid' : '' }}" value="{{ old('closes_at', $survey?->closes_at?->format('Y-m-d\TH:i')) }}">
        @error('closes_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
    </div>
</div>

<hr style="border-color:var(--line);margin:1.5rem 0;">

<h3 style="margin-bottom:.75rem;font-size:1rem;">{{ __('surveys.questions') }}</h3>
<div id="kia-survey-questions"></div>
<button type="button" class="btn btn-outline btn-sm" onclick="kiaSurveyAddQuestion()">{{ __('surveys.add_question') }}</button>

<template id="kia-survey-question-template">
    <div class="kia-card kia-survey-question" style="margin-top:1rem;">
        <div class="kia-card-body">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
                <strong class="kia-survey-question-number"></strong>
                <button type="button" class="btn btn-ghost btn-sm" onclick="this.closest('.kia-survey-question').remove()">{{ __('surveys.remove_question') }}</button>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('surveys.question_text_en') }}</label>
                <input type="text" class="form-control kia-q-text-en" maxlength="500" required>
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('surveys.question_text_km') }}</label>
                <input type="text" class="form-control kia-q-text-km" maxlength="500">
            </div>
            <div class="form-group">
                <label class="form-label">{{ __('surveys.question_type') }}</label>
                <select class="form-control kia-q-type" onchange="kiaSurveyToggleOptions(this)">
                    <option value="multiple_choice">{{ __('surveys.type_multiple_choice') }}</option>
                    <option value="rating_scale">{{ __('surveys.type_rating_scale') }}</option>
                    <option value="free_text">{{ __('surveys.type_free_text') }}</option>
                    <option value="yes_no">{{ __('surveys.type_yes_no') }}</option>
                </select>
            </div>
            <div class="form-group kia-q-options-wrap">
                <label class="form-label">{{ __('surveys.options') }}</label>
                <div class="kia-q-options"></div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="kiaSurveyAddOption(this)">{{ __('surveys.add_option') }}</button>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;">
                    <input type="checkbox" class="kia-q-required" checked>
                    {{ __('surveys.required_question') }}
                </label>
            </div>
        </div>
    </div>
</template>

<template id="kia-survey-option-template">
    <div style="display:flex;gap:.5rem;margin-bottom:.5rem;">
        <input type="text" class="form-control kia-q-option-input" maxlength="150">
        <button type="button" class="btn btn-ghost btn-sm" onclick="this.parentElement.remove()">×</button>
    </div>
</template>

<script>
function kiaSurveyToggleAudience() {
    var val = document.getElementById('audience').value;
    ['role', 'branch', 'class', 'section'].forEach(function (type) {
        var el = document.getElementById('kia-target-' + type);
        var show = type === val;
        el.style.display = show ? '' : 'none';
        el.querySelector('.kia-target-select').disabled = !show;
    });
}

function kiaSurveyAddOption(button, value) {
    var tpl = document.getElementById('kia-survey-option-template');
    var node = tpl.content.cloneNode(true);
    if (value) node.querySelector('.kia-q-option-input').value = value;
    button.parentElement.querySelector('.kia-q-options').appendChild(node);
}

function kiaSurveyToggleOptions(select) {
    var wrap = select.closest('.kia-survey-question').querySelector('.kia-q-options-wrap');
    wrap.style.display = select.value === 'multiple_choice' ? '' : 'none';
}

function kiaSurveyRenumber() {
    document.querySelectorAll('.kia-survey-question').forEach(function (block, i) {
        block.querySelector('.kia-survey-question-number').textContent = 'Q' + (i + 1);
    });
}

function kiaSurveyAddQuestion(existing) {
    var tpl = document.getElementById('kia-survey-question-template');
    var node = tpl.content.cloneNode(true);
    document.getElementById('kia-survey-questions').appendChild(node);
    var block = document.getElementById('kia-survey-questions').lastElementChild;

    if (existing) {
        block.querySelector('.kia-q-text-en').value = existing.question_text_en || '';
        block.querySelector('.kia-q-text-km').value = existing.question_text_km || '';
        block.querySelector('.kia-q-type').value = existing.type;
        block.querySelector('.kia-q-required').checked = !!existing.required;
        (existing.options || []).forEach(function (opt) {
            kiaSurveyAddOption(block.querySelector('.kia-q-options-wrap .btn'), opt);
        });
    }
    kiaSurveyToggleOptions(block.querySelector('.kia-q-type'));
    kiaSurveyRenumber();
}

// Serialize the dynamic question blocks into indexed form fields right before submit.
(function () {
    var form = document.getElementById('kia-survey-form');
    if (!form) return;

    form.addEventListener('submit', function () {
        document.querySelectorAll('#kia-survey-questions .kia-survey-question').forEach(function (block, i) {
            block.querySelectorAll('input[name], select[name]').forEach(function (el) { el.remove(); });

            var mk = function (name, value) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'questions[' + i + '][' + name + ']';
                input.value = value;
                form.appendChild(input);
            };

            mk('question_text_en', block.querySelector('.kia-q-text-en').value);
            mk('question_text_km', block.querySelector('.kia-q-text-km').value);
            mk('type', block.querySelector('.kia-q-type').value);
            mk('required', block.querySelector('.kia-q-required').checked ? '1' : '');

            block.querySelectorAll('.kia-q-option-input').forEach(function (optInput, j) {
                if (optInput.value.trim() !== '') mk('options[' + j + ']', optInput.value);
            });
        });
    });

    kiaSurveyToggleAudience();

    @foreach($existingQuestions as $q)
    kiaSurveyAddQuestion({
        question_text_en: @json($q->question_text_en),
        question_text_km: @json($q->question_text_km),
        type: @json($q->type),
        required: @json($q->required),
        options: @json($q->options ?? []),
    });
    @endforeach
})();
</script>
