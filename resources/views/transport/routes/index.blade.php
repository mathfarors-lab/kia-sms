<x-app-layout>
    <x-slot name="title">Transport Routes</x-slot>

    <div class="kia-page-header">
        <div><h1 class="kia-page-title">Transport Routes</h1></div>
        @can('manage', \App\Models\TransportRoute::class)
            <a href="{{ route('transport.routes.create') }}" class="btn btn-primary">+ Add Route</a>
        @endcan
    </div>

    <div class="kia-card">
        <div class="kia-table-wrap">
            <table class="kia-table">
                <thead><tr><th>Route</th><th>Fare/Month</th><th>Vehicles</th><th></th></tr></thead>
                <tbody>
                @forelse($routes as $route)
                    <tr>
                        <td>{{ $route->name }}</td>
                        <td>${{ $route->fare }}</td>
                        <td>{{ $route->vehicles_count }}</td>
                        <td style="display:flex;gap:.4rem">
                            @can('manage', $route)
                                <a href="{{ route('transport.vehicles.create', $route) }}" class="btn btn-ghost" style="font-size:.75rem">+ Vehicle</a>
                                <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-ghost" style="font-size:.75rem">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted)">No routes yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:1rem">{{ $routes->links() }}</div>
    </div>
</x-app-layout>
