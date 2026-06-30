@extends('layouts.app')
@section('title', $conversation->subject)
@section('content')
<div class="page-header">
    <h1>{{ $conversation->subject }}</h1>
    <a href="{{ route('conversations.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="card message-thread">
    @foreach($messages as $msg)
        <div class="message {{ $msg->sender_id === auth()->id() ? 'mine' : 'theirs' }}">
            <strong>{{ $msg->sender->name }}</strong>
            <p>{{ $msg->body }}</p>
            <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
        </div>
    @endforeach
</div>

<form method="POST" action="{{ route('conversations.reply', $conversation) }}" class="card form-card mt-4">
    @csrf
    <div class="form-group">
        <textarea name="body" rows="3" class="form-input" placeholder="Write a reply..." required></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Reply</button>
</form>
@endsection
