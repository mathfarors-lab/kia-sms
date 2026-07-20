<x-app-layout>
    <x-slot name="title">{{ __('feedback.submit_new') }}</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">{{ __('feedback.submit_new') }}</h1></div>
        <a href="{{ route('feedback.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card" style="max-width:640px;">
        <div class="kia-card-body">
            <form method="POST" action="{{ route('feedback.store') }}" enctype="multipart/form-data">
                @csrf

                @if($children->isNotEmpty())
                <div class="form-group">
                    <label class="form-label" for="student_id">{{ __('feedback.about_child') }}</label>
                    <select name="student_id" id="student_id" class="form-control">
                        <option value="">{{ __('feedback.general_complaint') }}</option>
                        @foreach($children as $child)
                        <option value="{{ $child->id }}" {{ (string) old('student_id') === (string) $child->id ? 'selected' : '' }}>
                            {{ $child->name_km ?: $child->name_en }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label" for="category">{{ __('feedback.category') }}</label>
                    <select name="category" id="category" class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}" required>
                        @foreach(\App\Models\FeedbackItem::CATEGORIES as $c)
                        <option value="{{ $c }}" {{ old('category') === $c ? 'selected' : '' }}>{{ __('feedback.category_' . $c) }}</option>
                        @endforeach
                    </select>
                    @error('category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">{{ __('feedback.subject') }}</label>
                    <input type="text" name="subject" id="subject" class="form-control {{ $errors->has('subject') ? 'is-invalid' : '' }}" value="{{ old('subject') }}" maxlength="150" required>
                    @error('subject')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="body">{{ __('feedback.body') }}</label>
                    <textarea name="body" id="body" rows="6" class="form-control {{ $errors->has('body') ? 'is-invalid' : '' }}" required>{{ old('body') }}</textarea>
                    @error('body')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="attachment">{{ __('feedback.attachment') }}</label>
                    <input type="file" name="attachment" id="attachment" class="form-control {{ $errors->has('attachment') ? 'is-invalid' : '' }}" accept=".pdf,.jpg,.jpeg,.png">
                    @error('attachment')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <button type="submit" class="btn btn-primary">{{ __('feedback.submit_new') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
