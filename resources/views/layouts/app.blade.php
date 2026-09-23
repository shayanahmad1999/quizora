<!doctype html>
<html lang="en" data-layout="{{ $theme['layout'] }}" data-bs-theme="{{ $theme['is_dark'] ? 'dark' : 'light' }}" style="--accent:{{ $theme['accent'] }};--background:{{ $theme['background'] }};--surface:{{ $theme['surface'] }};--ink:{{ $theme['text'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" data-csrf content="{{ csrf_token() }}">
    <meta data-login-url content="{{ route('login') }}">
    <meta name="theme-color" content="{{ $theme['accent'] }}">
    <title>{{ $title }} | {{ $settings->site_name }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <script src="{{ asset('assets/js/ajax.js') }}" defer></script>
</head>
<body>
    <div class="request-progress" data-progress hidden aria-hidden="true"></div>
    <noscript><p class="noscript">Please enable JavaScript to use automatic answer saving and page navigation.</p></noscript>
    <div data-app>@include('layouts.shell')</div>
    <div class="toast-stack" data-notices aria-live="polite" aria-atomic="false"></div>
    <dialog class="confirm-dialog" data-confirm-dialog aria-label="Confirm action">
        <span class="eyebrow">ONE MORE THING</span><h2>Are you sure?</h2>
        <p data-confirm-message></p>
        <div class="button-row end"><button type="button" class="btn btn-light" data-confirm-answer="cancel">Cancel</button><button type="button" class="btn btn-primary" data-confirm-answer="confirm">Continue</button></div>
    </dialog>
</body>
</html>
