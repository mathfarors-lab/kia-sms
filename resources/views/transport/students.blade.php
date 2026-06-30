@extends('layouts.app')
@section('title', 'Student Transport')
@section('content')
<div class="page-header">
    <h1>Student Transport — {{ $year?->name }}</h1>
</div>

@can('manage', \App\Models\TransportRoute::class)
<div class="card form-card mb-4">
    <h3>Assign Student</h3>
    <form method="POST" action="{{ route('transport.students.assign') }}">
        @csrf
        <div class="form-group">
            <label>Student</label>
            <select name="student_id" class="form-input" required>
                @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->name_en }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Vehicle</label>
            <select name="vehicle_id" class="form-input" required>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}">{{ $v->route->name }} – {{ $v->plate_no }} (cap: {{ $v->capacity }})</option>
                @endforeach
            </select>
        </div>
        @error('vehicle_id')<p class="form-error">{{ $message }}</p>@enderror
        <button class="btn btn-primary" type="submit">Assign</button>
    </form>
</div>
@endcan

<div class="card">
    @forelse($assigned as $st)
        <div class="list-item">
            <div>
                <strong>{{ $st->student->name_en }}</strong>
                <small>{{ $st->route->name }} · {{ $st->vehicle->plate_no }}</small>
            </div>
            @can('manage', \App\Models\TransportRoute::class)
                <form method="POST" action="{{ route('transport.students.remove', $st->student) }}"
                      onsubmit="return confirm('Remove assignment?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Remove</button>
                </form>
            @endcan
        </div>
    @empty
        <p class="empty-state">No students assigned to transport.</p>
    @endforelse
    {{ $assigned->links() }}
</div>
@endsection
