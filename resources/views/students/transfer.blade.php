<x-app-layout>
    <x-slot name="title">{{ __('student_transfer.transfer_title') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ __('student_transfer.transfer_title') }}</h1>
            <p class="kia-page-sub">{{ $student->name_km ?: $student->name_en }} — {{ $student->student_code }}</p>
        </div>
        <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    @if($errors->any())
    <div class="kia-alert kia-alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="kia-card" style="max-width:640px;">
        <div class="kia-card-body">
            @if($outstandingBalance > 0)
            <div class="kia-card" style="border-left:4px solid var(--warn);background:var(--paper);margin-bottom:1.5rem;">
                <div class="kia-card-body">
                    <strong style="color:var(--warn);">{{ __('student_transfer.outstanding_balance_warning', ['amount' => number_format($outstandingBalance, 2)]) }}</strong>
                    <p style="color:var(--muted);font-size:.875rem;margin-top:.5rem;">{{ __('student_transfer.outstanding_balance_note') }}</p>
                </div>
            </div>
            @endif

            <form method="POST" action="{{ route('students.transfer', $student) }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="reason_category">{{ __('student_transfer.reason') }}</label>
                    <select name="reason_category" id="reason_category" class="form-control {{ $errors->has('reason_category') ? 'is-invalid' : '' }}" required>
                        @foreach(\App\Models\StudentTransfer::REASON_CATEGORIES as $r)
                        <option value="{{ $r }}" {{ old('reason_category') === $r ? 'selected' : '' }}>{{ __('student_transfer.reason_' . $r) }}</option>
                        @endforeach
                    </select>
                    @error('reason_category')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="reason_note">{{ __('student_transfer.reason_note') }}</label>
                    <textarea name="reason_note" id="reason_note" rows="3" class="form-control">{{ old('reason_note') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="effective_date">{{ __('student_transfer.effective_date') }}</label>
                    <input type="date" name="effective_date" id="effective_date" class="form-control {{ $errors->has('effective_date') ? 'is-invalid' : '' }}" value="{{ old('effective_date', now()->toDateString()) }}" required>
                    @error('effective_date')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="destination_branch_id">{{ __('student_transfer.destination_branch') }}</label>
                    <select name="destination_branch_id" id="destination_branch_id" class="form-control {{ $errors->has('destination_branch_id') ? 'is-invalid' : '' }}">
                        <option value="">{{ __('student_transfer.destination_external') }}</option>
                        @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) old('destination_branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name_km ?: $branch->name_en }}</option>
                        @endforeach
                    </select>
                    @error('destination_branch_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="destination_name">{{ __('student_transfer.destination_name') }}</label>
                    <input type="text" name="destination_name" id="destination_name" class="form-control" value="{{ old('destination_name') }}" maxlength="150" placeholder="{{ __('student_transfer.destination_name_placeholder') }}">
                </div>

                @if($outstandingBalance > 0)
                <div class="form-group">
                    <label style="display:flex;align-items:flex-start;gap:.5rem;font-weight:400;">
                        <input type="checkbox" name="acknowledge_balance" value="1" {{ old('acknowledge_balance') ? 'checked' : '' }} required style="margin-top:.2rem;">
                        <span>{{ __('student_transfer.acknowledge_balance_checkbox', ['amount' => number_format($outstandingBalance, 2)]) }}</span>
                    </label>
                </div>
                @endif

                <button type="submit" class="btn btn-primary" onclick="return confirm('{{ __('student_transfer.transfer_confirm') }}')">{{ __('student_transfer.transfer_title') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>
