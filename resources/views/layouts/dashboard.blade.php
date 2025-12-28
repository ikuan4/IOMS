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
    .notification-toast { position: fixed; top: 22px; right: 22px; display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:10px; box-shadow:0 8px 24px rgba(0,0,0,0.06); opacity:0; transform:translateY(-6px); transition:opacity 300ms ease, transform 300ms ease; z-index:2000; min-width:280px; max-width:420px; }
    .notification-toast.show { opacity:1; transform:translateY(0); }
    .notification-icon { display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; background:rgba(255,255,255,0.12); flex:0 0 36px; }
    .notification-content { flex:1 1 auto; }
    .notification-message { font-weight:700; font-size:14px; color:#0f172a; }
    .notification-close { background:transparent;border:none;cursor:pointer;color:inherit;padding:6px;display:flex;align-items:center;justify-content:center;border-radius:6px; }
    .notification-success { background: linear-gradient(180deg,#ecfdf5,#bbf7d0); border:1px solid #10b981; }
    .notification-error, .notification-deleted { background: linear-gradient(180deg,#ffeaea,#fecaca); border:1px solid #ef4444; }
    .notification-deleted .notification-message { color:#7f1d1d; }
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

    <main class="main">
        @yield('content')
    </main>
</div>

@include('partials.confirmation-modal')

<!-- Dashboard JS -->
@vite(['resources/js/dashboard.js'])

@stack('scripts')
</body>
</html>
