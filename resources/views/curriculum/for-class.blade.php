<x-app-layout>
    <x-slot name="title">{{ $class->name }} — {{ __('curriculum.index_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ $class->name }} — {{ __('curriculum.index_title') }}</h1>
        <a href="{{ route('curriculum.index') }}" class="btn btn-ghost">{{ __('Back') }}</a>
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('curriculum.subject') }}</th>
                        <th>{{ __('curriculum.teacher') }}</th>
                        <th>{{ __('curriculum.progress') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($class->classSubjects as $classSubject)
                    @php
                        $total = $classSubject->curriculum_topics_count;
                        $done = $classSubject->completed_topics_count;
                        $pct = $total > 0 ? round(($done / $total) * 100) : 0;
                    @endphp
                    <tr>
                        <td>{{ $classSubject->subject->name_en }}</td>
                        <td>{{ $classSubject->teacher?->user?->name ?? '—' }}</td>
                        <td>
                            @if($total > 0)
                                {{ $done }} / {{ $total }} ({{ $pct }}%)
                            @else
                                <span style="color:var(--muted);">{{ __('curriculum.no_topics_yet') }}</span>
                            @endif
                        </td>
                        <td style="text-align:right;"><a href="{{ route('curriculum.show', $classSubject) }}" class="btn btn-ghost btn-sm">{{ __('curriculum.view_syllabus') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="kia-table-empty">{{ __('curriculum.no_classes_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
