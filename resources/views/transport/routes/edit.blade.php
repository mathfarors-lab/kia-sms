@extends('layouts.app')
@section('title', 'Edit Route')
@section('content')
<div class="page-header"><h1>Edit Route</h1></div>
<form method="POST" action="{{ route('transport.routes.update', $route) }}" class="card form-card">
    @csrf @method('PATCH')
    <div class="form-group">
        <label>Route Name *</label>
        <input type="text" name="name" value="{{ old('name', $route->name) }}" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3" class="form-input">{{ old('description', $route->description) }}</textarea>
    </div>
    <div class="form-group">
        <label>Monthly Fare (USD)</label>
        <input type="number" name="fare" class="form-input" step="0.01" min="0" value="{{ old('fare', $route->fare) }}">
    </div>
    <button class="btn btn-primary" type="submit">Update</button>
    <a href="{{ route('transport.routes.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
