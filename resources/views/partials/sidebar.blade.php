<aside class="sidebar" id="sidebar" aria-hidden="false">
    <div class="brand">
        <div class="brand-logo">I</div>
        <div class="brand-text">
            <h1>Modules</h1>
        </div>
    </div>

    <nav class="nav" aria-label="Main navigation">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span data-feather="home"></span>
            <span class="label">Dashboard</span>
        </a>

        {{-- Users (only this module replicated) --}}
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span data-feather="users"></span>
            <span class="label">Users</span>
        </a>
    </nav>

    <div class="sidebar-footer muted">
        <span class="label">© {{ date('Y') }} IOMS</span>
    </div>
</aside>
