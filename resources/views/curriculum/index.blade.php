<x-app-layout>
    <x-slot name="title">{{ __('curriculum.index_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('curriculum.index_title') }}</h1>
    </div>

    <p style="color:var(--muted);font-size:.875rem;margin-bottom:1rem;">{{ __('curriculum.select_a_class') }}</p>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('curriculum.class') }}</th>
                        <th>{{ __('curriculum.subjects_count') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->name }}</td>
                        <td>{{ $class->class_subjects_count }}</td>
                        <td style="text-align:right;"><a href="{{ route('curriculum.for-class', $class) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="kia-table-empty">{{ __('curriculum.no_classes_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $classes->links() }}
</x-app-layout>
