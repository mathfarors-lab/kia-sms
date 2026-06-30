@extends('layouts.app')
@section('title', 'Homework')
@section('content')
<div class="page-header">
    <h1>Homework</h1>
    @can('create', \App\Models\Homework::class)
        <a href="{{ route('homework.create') }}" class="btn btn-primary">+ Assign</a>
    @endcan
</div>
<div class="card">
    @forelse($homework as $hw)
        <div class="list-item">
            <div>
                <a href="{{ route('homework.show', $hw) }}"><strong>{{ $hw->title }}</strong></a>
                <small>{{ $hw->section->name }} · {{ $hw->subject->name }}</small>
            </div>
            <small class="text-muted">Due: {{ $hw->due_date->format('d M Y') }}</small>
        </div>
    @empty
        <p class="empty-state">No homework yet.</p>
    @endforelse
    {{ $homework->links() }}
</div>
@endsection
