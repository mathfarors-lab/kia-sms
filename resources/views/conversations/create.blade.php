@extends('layouts.app')
@section('title', 'New Message')
@section('content')
<div class="page-header"><h1>New Message</h1></div>
<form method="POST" action="{{ route('conversations.store') }}" class="card form-card">
    @csrf
    @if($recipient)
        <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
        <p>To: <strong>{{ $recipient->name }}</strong></p>
    @else
        <div class="form-group">
            <label>Recipient ID</label>
            <input type="number" name="recipient_id" class="form-input" required>
        </div>
    @endif
    <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" class="form-input" required>
    </div>
    <div class="form-group">
        <label>Message</label>
        <textarea name="body" rows="5" class="form-input" required></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Send</button>
</form>
@endsection
