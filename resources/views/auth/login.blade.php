{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - IOMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/MSHCS_logo.png') }}" />

    {{-- Prevent global theme script from forcing dark on this page --}}
    <script>
        (function () {
            try {
                // ensure no dark flash before CSS loads
                document.documentElement.classList.remove('dark');
                if (document.body) document.body.classList.remove('dark');

                // tell theme script to skip applying theme on this page
                sessionStorage.setItem('skip-theme', '1');
            } catch (e) {}
        })();
    </script>

    {{-- Load app CSS/JS via Vite --}}
    @vite(['resources/css/login.css', 'resources/js/login.js'])
</head>

<body class="layout-root">

    <main class="login-card" role="main" aria-labelledby="login-heading">
        <!-- Left: form -->
        <section class="login-left" aria-label="Sign in">
            <div class="form-wrap">

                {{-- Branding --}}
                <div class="brand" style="margin-bottom:12px;">
                    <img src="{{ asset('images/MSHCS_logo.png') }}" alt="MSHCS Logo" />
                    <div class="brand-text">
                        <div class="title-line">INTEGRATED OFFICE MANAGEMENT SYSTEM (IOMS)</div>
                    </div>
                </div>

                <div style="height:18px;"></div>

                {{-- Mobile / Email toggle --}}
                <div class="auth-toggle" style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
                    <div class="toggle-label">Sign in with</div>
                    <div class="toggle-buttons" role="tablist" aria-label="Sign in method">
                        <button type="button" class="toggle-btn active" data-mode="mobile" aria-pressed="true">Mobile</button>
                        <button type="button" class="toggle-btn" data-mode="email" aria-pressed="false">Email</button>
                    </div>
                </div>

                {{-- Error summary --}}
                @if ($errors->any())
                    <div class="errors" role="alert" aria-live="assertive" style="margin-bottom:14px;">
                        <ul style="margin:0; padding-left:18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Login form --}}
                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf

                    {{-- Mobile --}}
                    <div class="form-group">
                        <label for="mobile" id="mobile-label" style="font-weight:600;">Mobile number</label>
                        <label for="email" id="email-label" style="display:none; font-weight:600;">Email address</label>

                        <div style="position:relative;">
                            <input
                                id="mobile"
                                name="mobile"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{10}"
                                maxlength="10"
                                value="{{ old('mobile') }}"
                                required
                                autofocus
                                placeholder="Enter 10 digit mobile number"
                                class="input"
                                aria-describedby="mobile-help"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            />

                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="Enter your email address"
                                class="input"
                                aria-describedby="email-help"
                                style="display:none;"
                                disabled
                            />
                        </div>

                        <div id="mobile-help" style="font-size:12px; color:var(--muted); margin-top:6px;">
                            Enter 10 digit mobile number (numbers only).
                        </div>
                        <div id="email-help" style="font-size:12px; color:var(--muted); margin-top:6px; display:none;">
                            Enter the email address associated with your account.
                        </div>
                        @error('mobile')
                            <p class="text-red-600 text-sm mt-1" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label for="password" style="margin:0; font-weight:600;">Password</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-green-600 hover:underline" style="font-size:13px;">
                                    Forgot Password
                                </a>
                            @endif
                        </div>

                        <div style="position:relative;">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                placeholder="Your password"
                                class="input"
                                aria-describedby="password-help"
                            />

                            {{-- Icon button sits absolutely inside the input area --}}
                            <button id="pwd-toggle" type="button" aria-label="Toggle password visibility"
                                    style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:transparent; border:0; padding:0; cursor:pointer; display:flex; align-items:center; justify-content:center;">
                                {{-- Eye open (visible when password is masked) --}}
                                <svg id="eye-on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" style="width:24px; height:24px; color: #6b7280;">
                                    <path fill="currentColor" d="M32 16C18.7 16 7.4 24.6 2 36c5.4 11.4 16.7 20 30 20s24.6-8.6 30-20C56.6 24.6 45.3 16 32 16zm0 33.3c-7.4 0-13.3-6-13.3-13.3S24.6 22.7 32 22.7 45.3 28.6 45.3 36 39.4 49.3 32 49.3z"/>
                                    <circle fill="currentColor" cx="32" cy="36" r="8.7"/>
                                    <path fill="currentColor" d="M5.3 2.7l5.3 5.3 5.4 5.3 37.3 37.4 5.4 5.3-3.7 3.7-5.3-5.3L13.3 17 7.9 11.7 2.7 6.3z" opacity="0"/>
                                </svg>

                                {{-- Eye closed (hidden by default, visible when password is shown) --}}
                                <svg id="eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" style="width:24px; height:24px; color: #6b7280; display:none;">
                                    <path fill="currentColor" d="M32 22.7c7.4 0 13.3 6 13.3 13.3 0 1.5-.3 2.9-.7 4.2l7.8 7.8c3.8-3.4 6.9-7.6 9.3-12C56.6 24.6 45.3 16 32 16c-3.2 0-6.3.5-9.2 1.4l5.8 5.8c1.3-.3 2.7-.5 4.1-.5z"/>
                                    <path fill="currentColor" d="M5.3 9.3L12 16l2.3 2.3C9.9 21.7 6.7 26 4.3 30.7c-.6 1.1-.6 2.5 0 3.7C9.4 45.7 20.7 54 32 54c3.4 0 6.6-.6 9.7-1.6L44 54.7l7.3 7.3 3.7-3.7-52-52L5.3 9.3zm13.4 13.4l4.1 4.1c-.2.7-.3 1.4-.3 2.2 0 4.8 3.9 8.7 8.7 8.7.8 0 1.5-.1 2.2-.3l4.1 4.1c-1.9.9-4.1 1.5-6.3 1.5-7.4 0-13.3-6-13.3-13.3 0-2.2.5-4.4 1.5-6.3zm7.6-1.3l10.6 10.6.1-.7c0-4.8-3.9-8.7-8.7-8.7l-.7.1z"/>
                                </svg>
                            </button>
                        </div>

                        <div id="password-help" style="font-size:12px; color:var(--muted); margin-top:6px;">
                            Your password is case-sensitive.
                        </div>

                        @error('password')
                            <p class="text-red-600 text-sm mt-1" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember --}}
                    <div class="remember-row" style="margin-top:6px; margin-bottom:14px;">
                        <input id="remember" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }} />
                        <label for="remember" style="margin:0 0 0 6px;">Remember me on this device</label>
                    </div>

                    <div style="margin-top:6px;">
                        <button type="submit" class="btn-submit" style="width:100%;">
                            Sign in
                        </button>
                    </div>
                </form>

                <div class="divider" style="margin-top:18px;">
                    <span>OR</span>
                </div>

                <p class="helper" style="margin-top:12px;">
                    Don't have an account yet?
                    <span style="color:var(--accent); cursor:default;">Contact Administrator</span>
                </p>
            </div>
        </section>

        <!-- Right: Illustration -->
        <aside class="login-right" aria-hidden="true">
            <img src="{{ asset('images/login-illustration.svg') }}" alt="Login Illustration" class="illustration" />
        </aside>
    </main>
</body>
</html>
