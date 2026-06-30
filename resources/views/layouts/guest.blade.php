<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'KIA School') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite(['resources/css/kia.css', 'resources/js/app.js'])
</head>
<body>
<div class="kia-auth-wrap">
    <div class="kia-auth-card">
        <div class="kia-auth-logo">
            <div class="kia-auth-logo-badge">KIA</div>
            <h1>KIA School</h1>
            <p>{{ __('Khmer Intellectual Academy') }}</p>
        </div>
        {{ $slot }}
    </div>
</div>
</body>
</html>
