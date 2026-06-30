@extends('layouts.app')
@section('title', 'Add Vehicle')
@section('content')
<div class="page-header"><h1>Add Vehicle to {{ $route->name }}</h1></div>
<form method="POST" action="{{ route('transport.vehicles.store', $route) }}" class="card form-card">
    @csrf
    <div class="form-group">
        <label>Plate No *</label>
        <input type="text" name="plate_no" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Driver Name *</label>
        <input type="text" name="driver_name" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Driver Phone</label>
        <input type="text" name="driver_phone" class="form-input">
    </div>
    <div class="form-group">
        <label>Capacity (seats) *</label>
        <input type="number" name="capacity" class="form-input" min="1" required>
    </div>
    <button class="btn btn-primary" type="submit">Save</button>
    <a href="{{ route('transport.routes.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
