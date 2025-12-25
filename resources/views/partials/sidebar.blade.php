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

        @php
            $isRoleModule = request()->routeIs('roles.*');
            $isUserModule = request()->routeIs('users.*');
            $isHierarchyPage = request()->routeIs('roles.hierarchy') || request()->routeIs('roles.hierarchy.*');
            $isUserMgmtModule = $isRoleModule || $isUserModule;
        @endphp

        {{-- User Management module (collapsible group) --}}
        @auth
        <div class="nav-group {{ $isUserMgmtModule ? 'open' : '' }}" id="nav-user-mgmt">
            <button
                type="button"
                class="nav-toggle"
                id="nav-user-mgmt-toggle"
                aria-expanded="{{ $isUserMgmtModule ? 'true' : 'false' }}"
                aria-controls="nav-user-mgmt-submenu"
            >
                <span data-feather="users"></span>
                <span class="label">User Management</span>
                <span class="nav-toggle-caret" aria-hidden="true" data-feather="chevron-down"></span>
            </button>

            <div class="nav-submenu" id="nav-user-mgmt-submenu">
                @can('viewAny', \App\Models\User::class)
                <a href="{{ route('users.index') }}" class="{{ $isUserModule ? 'active' : '' }}">
                    <span class="label">Manage Users</span>
                </a>
                @endcan
                @if(auth()->user()->can('viewAny', \App\Models\Role::class))
                <a href="{{ route('roles.index') }}" class="{{ ($isRoleModule && !$isHierarchyPage) ? 'active' : '' }}">
                    <span class="label">Manage Roles</span>
                </a>
                @endif
                @can('viewAny', \App\Models\Branch::class)
                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    <span class="label">Manage Branches</span>
                </a>
                @endcan
                @if(Route::has('roles.hierarchy') && auth()->user()->can('viewAny', \App\Models\Role::class))
                    @php
                        $roleId = request()->route('role') ?? (\App\Models\Role::first() ? \App\Models\Role::first()->id : 1);
                    @endphp
                    <a href="{{ route('roles.hierarchy', $roleId) }}" class="{{ $isHierarchyPage ? 'active' : '' }}">
                        <span class="label">Manage Hierarchy</span>
                    </a>
                @endif
            </div>
        </div>
        @endauth
    </nav>

    <div class="sidebar-footer muted">
        <span class="label">© {{ date('Y') }} IOMS</span>
    </div>
</aside>
