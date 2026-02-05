<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>@yield('title', 'IOMS') - IOMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/MSHCS_logo.png') }}" />

    <!-- Load Dashboard CSS -->
    @vite([
        'resources/css/app.css',
        'resources/css/dashboard.css',
        'resources/js/app.js',
        'resources/js/dashboard.js',
        'resources/js/spa-navigation.js',
    ])

    <!-- Feather icons -->
    <script src="https://unpkg.com/feather-icons"></script>

    @stack('styles')
    @stack('head')
</head>
<body>

@include('partials.header')

{{-- Notification Messages --}}
@if(session('status'))
<div class="notification-toast notification-success show" id="notification-toast">
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
<div class="notification-toast notification-error show" id="notification-toast">
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

@if(session('deleted'))
<div class="notification-toast notification-deleted show" id="notification-toast">
    <div class="notification-icon">
        <span data-feather="trash-2"></span>
    </div>
    <div class="notification-content">
        <div class="notification-message">{{ session('deleted') }}</div>
    </div>
    <button class="notification-close" onclick="closeNotification()">
        <span data-feather="x"></span>
    </button>
</div>
@endif

<style>
    .notification-toast {
        position: fixed;
        top: 22px;
        right: 22px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        opacity: 0;
        transform: translateY(-6px);
        transition: opacity 300ms ease, transform 300ms ease;
        z-index: 2000;
        min-width: 280px;
        max-width: 420px;
    }
    .notification-toast.show {
        opacity: 1;
        transform: translateY(0);
    }
    .notification-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(255,255,255,0.15);
        flex: 0 0 36px;
    }
    .notification-content {
        flex: 1 1 auto;
    }
    .notification-message {
        font-weight: 700;
        font-size: 14px;
        color: var(--text, #0f172a);
    }
    .notification-close {
        background: transparent;
        border: none;
        cursor: pointer;
        color: inherit;
        padding: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        opacity: 0.8;
        transition: opacity 0.2s;
    }
    .notification-close:hover {
        opacity: 1;
    }

    /* Light theme toasts */
    .notification-success {
        background: linear-gradient(180deg, #ecfdf5, #bbf7d0);
        border: 1px solid #10b981;
    }
    .notification-success .notification-message {
        color: #065f46;
    }
    .notification-success .notification-icon {
        color: #10b981;
    }

    .notification-error,
    .notification-deleted {
        background: linear-gradient(180deg, #ffeaea, #fecaca);
        border: 1px solid #ef4444;
    }
    .notification-error .notification-message,
    .notification-deleted .notification-message {
        color: #7f1d1d;
    }
    .notification-error .notification-icon,
    .notification-deleted .notification-icon {
        color: #ef4444;
    }

    /* Dark theme toasts */
    /* SPA Loading Styles */
    .spa-loading-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.05); z-index: 9999; align-items: center; justify-content: center; }
    .spa-spinner { width: 40px; height: 40px; border: 3px solid #f3f4f6; border-top-color: #3b82f6; border-radius: 50%; animation: spa-spin 0.8s linear infinite; }
    @keyframes spa-spin { to { transform: rotate(360deg); } }
    main.main.spa-loading { opacity: 0.6; pointer-events: none; transition: opacity 0.2s ease; }

    [data-theme="dark"] .notification-success {
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
        border: 1px solid rgba(16, 185, 129, 0.4);
    }
    [data-theme="dark"] .notification-success .notification-message {
        color: #6ee7b7;
    }
    [data-theme="dark"] .notification-success .notification-icon {
        background: rgba(16, 185, 129, 0.2);
        color: #6ee7b7;
    }

    [data-theme="dark"] .notification-error,
    [data-theme="dark"] .notification-deleted {
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.2), rgba(239, 68, 68, 0.1));
        border: 1px solid rgba(239, 68, 68, 0.4);
    }
    [data-theme="dark"] .notification-error .notification-message,
    [data-theme="dark"] .notification-deleted .notification-message {
        color: #fca5a5;
    }
    [data-theme="dark"] .notification-error .notification-icon,
    [data-theme="dark"] .notification-deleted .notification-icon {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }

    /* Pagination per-page dropdown: use a custom arrow so we can position it consistently.
       This targets the shared per-page select used across index/list pages. */
    select[name="per_page"] {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5.5 7.5L10 12l4.5-4.5' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-size: 16px 16px !important;
        /* move arrow left by +4px (increase right inset) */
        background-position: right 26px center !important;
        padding-right: 56px !important;
    }

    [data-theme="dark"] select[name="per_page"] {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M5.5 7.5L10 12l4.5-4.5' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
    }
</style>

<script>
    // Auto-show and auto-dismiss notification toasts. Keep behavior minimal and unobtrusive.
    document.addEventListener('DOMContentLoaded', function(){
        try {
            const toasts = document.querySelectorAll('.notification-toast');
            toasts.forEach(function(t){
                // small defer so CSS transition plays
                setTimeout(()=> t.classList.add('show'), 50);
                // auto-dismiss after 30s (30000ms)
                const autoClose = setTimeout(()=> closeNotification(t), 30000);
                // store timer so manual close can cancel
                t._autoCloseTimer = autoClose;
            });
        } catch (e) { console.error(e); }
    });

    function closeNotification(el){
        try {
            let t = el;
            if (!t) t = document.querySelector('.notification-toast');
            if (!t) return;
            if (t._autoCloseTimer) { clearTimeout(t._autoCloseTimer); }
            t.classList.remove('show');
            setTimeout(()=> { try { t.remove(); } catch(e){} }, 350);
        } catch (e) { console.error(e); }
    }
</script>

<div class="app">
    @include('partials.sidebar')

    <main class="main" id="pjax-container">
        @yield('content')
    </main>
</div>

@include('partials.confirmation-modal')

<!-- Dashboard JS -->


@stack('scripts')
</body>
</html>
