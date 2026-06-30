<x-app-layout>
    <x-slot name="title">Classes</x-slot>

    <div class="kia-page-header">
        <div>
            <h1 class="kia-page-title">Classes</h1>
            <p class="kia-page-sub">{{ $classes->total() }} total</p>
        </div>
        <a href="{{ route('classes.create') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Class
        </a>
    </div>

    @if(session('success'))
        <div class="kia-alert kia-alert-success">{{ session('success') }}</div>
    @endif

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Level</th>
                        <th>Capacity</th>
                        <th>Sections</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->name }}</td>
                        <td>{{ $class->level ?? '—' }}</td>
                        <td>{{ $class->capacity }}</td>
                        <td>
                            <a href="{{ route('classes.sections.index', $class) }}" class="btn btn-sm btn-ghost">
                                {{ $class->sections_count }} sections
                            </a>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('classes.show', $class) }}" class="btn btn-sm btn-ghost">View</a>
                            <a href="{{ route('classes.edit', $class) }}" class="btn btn-sm btn-ghost">Edit</a>
                            <form method="POST" action="{{ route('classes.destroy', $class) }}" style="display:inline" onsubmit="return confirm('Delete this class?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center">No classes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $classes->links() }}</div>
    </div>
</x-app-layout>
