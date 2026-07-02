<x-app-layout>
    <x-slot name="title">Messages</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Messages</h1></div>
        <a href="{{ route('conversations.create') }}" class="btn btn-primary">New Message</a>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr>
                    <th>Subject</th><th>Participants</th><th>Last message</th>
                </tr></thead>
                <tbody>
                @forelse($conversations as $conv)
                    <tr>
                        <td><a href="{{ route('conversations.show', $conv) }}">{{ $conv->subject }}</a></td>
                        <td>{{ $conv->participants->pluck('name')->join(', ') }}</td>
                        <td>{{ $conv->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--text-muted)">No conversations yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $conversations->links() }}</div>
    </div>
</x-app-layout>
