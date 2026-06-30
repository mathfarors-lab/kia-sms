<x-app-layout>
    <x-slot name="title">{{ __('Settings') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('School Settings') }}</h1>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" style="max-width:760px;">
        @csrf

        @foreach($settings as $group => $groupSettings)
        <div class="kia-card" style="margin-bottom:20px;">
            <div class="kia-card-header">
                <h2 class="kia-card-title">{{ ucfirst($group) }}</h2>
            </div>
            <div class="kia-card-body">
                <div class="form-grid">
                    @foreach($groupSettings as $setting)
                    <div class="form-group">
                        <label class="form-label" for="setting_{{ $setting->key }}">
                            {{ ucwords(str_replace(['_', '-'], ' ', $setting->key)) }}
                        </label>
                        <input type="text"
                               id="setting_{{ $setting->key }}"
                               name="settings[{{ $setting->key }}]"
                               class="form-control"
                               value="{{ old("settings.{$setting->key}", $setting->value) }}">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
    </form>
</x-app-layout>
