<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'IOMS') - IOMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/MSHCS_logo.png') }}" />

    <!-- Load Dashboard CSS -->
    @vite(['resources/css/dashboard.css'])

    <!-- Feather icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    @stack('styles')
    @stack('head')
</head>
<body>

@include('partials.header')

{{-- Notification Messages --}}
@if(session('status'))
<div class="notification-toast notification-success" id="notification-toast">
    <div class="notification-icon">
        <span data-feather="check-circle"></span>
    </div>
    <div class="notification-content">
        <div class="notification-message">{{ session('status') }}</div>
    </div>
    <button class="notification-close" onclick="closeNotification()">
        <span data-feather="x"></span>
    </button>
</div>
@endif

@if(session('error'))
<div class="notification-toast notification-error" id="notification-toast">
    <div class="notification-icon">
        <span data-feather="alert-circle"></span>
    </div>
    <div class="notification-content">
        <div class="notification-message">{{ session('error') }}</div>
    </div>
    <button class="notification-close" onclick="closeNotification()">
        <span data-feather="x"></span>
    </button>
</div>
@endif

<div class="app">
    @include('partials.sidebar')

    <main class="main">
        @yield('content')
    </main>
</div>

<!-- Dashboard JS -->
@vite(['resources/js/dashboard.js'])

@stack('scripts')
</body>
</html>
