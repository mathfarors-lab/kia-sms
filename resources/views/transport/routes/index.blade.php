@extends('layouts.app')
@section('title', 'Transport Routes')
@section('content')
<div class="page-header">
    <h1>Transport Routes</h1>
    @can('manage', \App\Models\TransportRoute::class)
        <a href="{{ route('transport.routes.create') }}" class="btn btn-primary">+ Add Route</a>
    @endcan
</div>
<div class="card">
    @forelse($routes as $route)
        <div class="list-item">
            <div>
                <strong>{{ $route->name }}</strong>
                <small>Fare: ${{ $route->fare }} · {{ $route->vehicles_count }} vehicle(s)</small>
            </div>
            <div class="actions">
                @can('manage', $route)
                    <a href="{{ route('transport.vehicles.create', $route) }}" class="btn btn-sm btn-secondary">+ Vehicle</a>
                    <a href="{{ route('transport.routes.edit', $route) }}" class="btn btn-sm btn-secondary">Edit</a>
                @endcan
            </div>
        </div>
    @empty
        <p class="empty-state">No routes yet.</p>
    @endforelse
    {{ $routes->links() }}
</div>
@endsection
