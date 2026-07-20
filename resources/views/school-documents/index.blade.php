<x-app-layout>
    <x-slot name="title">{{ __('school_documents.section_title') }}</x-slot>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('school_documents.section_title') }}</h1>
        @can('documents.manage')
        <a href="{{ route('school-documents.create') }}" class="btn btn-primary">{{ __('school_documents.upload_document') }}</a>
        @endcan
    </div>

    @if(session('success'))<div class="kia-alert kia-alert-success">{{ session('success') }}</div>@endif

    <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap;">
        <a href="{{ route('school-documents.index') }}" class="btn btn-sm {{ !$category ? 'btn-primary' : 'btn-ghost' }}">{{ __('school_documents.all_categories') }}</a>
        @foreach(\App\Models\SchoolDocument::CATEGORIES as $cat)
        <a href="{{ route('school-documents.index', ['category' => $cat]) }}" class="btn btn-sm {{ $category === $cat ? 'btn-primary' : 'btn-ghost' }}">{{ __('school_documents.category_'.$cat) }}</a>
        @endforeach
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('school_documents.title') }}</th>
                        <th>{{ __('school_documents.category') }}</th>
                        <th>{{ __('school_documents.scope') }}</th>
                        <th>{{ __('school_documents.uploaded_by') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                    <tr>
                        <td>{{ $document->title }}</td>
                        <td><span class="pill pill-muted">{{ __('school_documents.category_'.$document->category) }}</span></td>
                        <td>{{ $document->branch_id === null ? __('school_documents.scope_all_branches') : __('school_documents.scope_this_branch') }}</td>
                        <td>{{ $document->uploader?->name ?? '—' }}</td>
                        <td style="text-align:right;display:flex;gap:.4rem;justify-content:flex-end;">
                            <a href="{{ route('school-documents.download', $document) }}" class="btn btn-ghost btn-sm">{{ __('Download') }}</a>
                            @can('documents.manage')
                            <form method="POST" action="{{ route('school-documents.destroy', $document) }}" onsubmit="return confirm('{{ __('school_documents.confirm_delete') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="kia-table-empty">{{ __('school_documents.none_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
