<x-app-layout>
    <x-slot name="title">{{ $isInbox ? __('feedback.inbox_title') : __('feedback.my_feedback') }}</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">{{ $isInbox ? __('feedback.inbox_title') : __('feedback.my_feedback') }}</h1>
            <p class="kia-page-sub">{{ $items->total() }} {{ __('total') }}</p>
        </div>
        <div style="display:flex;gap:.5rem">
            @can('feedback.view')
            <a href="{{ route('feedback.dashboard') }}" class="btn btn-ghost">{{ __('feedback.dashboard_title') }}</a>
            @endcan
            @unless($isInbox)
            <a href="{{ route('feedback.create') }}" class="btn btn-primary">{{ __('feedback.submit_new') }}</a>
            @endunless
        </div>
    </div>

    @if(session('success'))
    <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    @if($isInbox)
    <form method="GET" class="kia-filter-bar">
        <select name="status" class="form-control" style="min-width:150px;">
            <option value="">{{ __('feedback.all_statuses') }}</option>
            @foreach(\App\Models\FeedbackItem::STATUSES as $s)
            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ __('feedback.status_' . $s) }}</option>
            @endforeach
        </select>
        <select name="category" class="form-control" style="min-width:150px;">
            <option value="">{{ __('feedback.all_categories') }}</option>
            @foreach(\App\Models\FeedbackItem::CATEGORIES as $c)
            <option value="{{ $c }}" {{ request('category') == $c ? 'selected' : '' }}>{{ __('feedback.category_' . $c) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline btn-sm">{{ __('Filter') }}</button>
        @if(request()->hasAny(['status','category']))
        <a href="{{ route('feedback.index') }}" class="btn btn-ghost btn-sm">{{ __('Clear') }}</a>
        @endif
    </form>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>{{ __('feedback.subject') }}</th>
                        <th>{{ __('feedback.category') }}</th>
                        @if($isInbox)<th>{{ __('feedback.submitted_by') }}</th>@endif
                        <th>{{ __('feedback.status') }}</th>
                        @if($isInbox)<th>{{ __('feedback.assigned_to') }}</th>@endif
                        <th>{{ __('feedback.submitted_on') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    @php
                        $colors = ['open' => 'pill-warn', 'in_progress' => 'pill-royal', 'resolved' => 'pill-ok', 'closed' => 'pill-muted'];
                    @endphp
                    <tr>
                        <td><a href="{{ route('feedback.show', $item) }}" class="kia-link">{{ $item->subject }}</a></td>
                        <td>{{ __('feedback.category_' . $item->category) }}</td>
                        @if($isInbox)<td>{{ $item->submitter->name ?? '—' }}</td>@endif
                        <td><span class="pill {{ $colors[$item->status] ?? 'pill-muted' }}">{{ __('feedback.status_' . $item->status) }}</span></td>
                        @if($isInbox)<td>{{ $item->assignee->name ?? __('feedback.unassigned') }}</td>@endif
                        <td>{{ $item->created_at->format('d M Y') }}</td>
                        <td style="text-align:right;"><a href="{{ route('feedback.show', $item) }}" class="btn btn-ghost btn-sm">{{ __('View') }}</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $isInbox ? 7 : 5 }}" class="kia-table-empty">{{ __('feedback.no_items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
        <div class="kia-pagination">{{ $items->links() }}</div>
        @endif
    </div>
</x-app-layout>
