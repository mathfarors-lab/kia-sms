@extends('layouts.app')
@section('title', 'New Route')
@section('content')
<div class="page-header"><h1>New Transport Route</h1></div>
<form method="POST" action="{{ route('transport.routes.store') }}" class="card form-card">
    @csrf
    <div class="form-group">
        <label>Route Name *</label>
        <input type="text" name="name" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="3" class="form-input"></textarea>
    </div>
    <div class="form-group">
        <label>Monthly Fare (USD)</label>
        <input type="number" name="fare" class="form-input" step="0.01" min="0" value="0">
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('transport.routes.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
