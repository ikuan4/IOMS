<aside class="sidebar" id="sidebar" aria-hidden="false">
    <div class="brand">
        <div class="brand-logo">I</div>
        <div class="brand-text">
            <h1>Modules</h1>
        </div>
    </div>

    <nav class="nav" aria-label="Main navigation">
        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('dashboard.view')))
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span data-feather="home"></span>
            <span class="label">Dashboard</span>
        </a>
        @endif

        @php
            $isRoleModule = request()->routeIs('roles.*');
            $isUserModule = request()->routeIs('users.*');
            $isBranchModule = request()->routeIs('branches.*');
            $isUserMgmtModule = $isRoleModule || $isUserModule || $isBranchModule;
            $isHierarchyPage = request()->routeIs('roles.hierarchy') || request()->routeIs('roles.hierarchy.*');
        @endphp

        {{-- User Management module (collapsible group) --}}
        @auth
        @php
            $canViewUsers = auth()->user()->can('viewAny', \App\Models\User::class);
            $canViewRoles = auth()->user()->can('viewAny', \App\Models\Role::class);
            $canViewBranches = auth()->user()->can('viewAny', \App\Models\Branch::class);
            $canManageHierarchy = (auth()->user()->isSuperAdmin()) || (auth()->user()->hasPermission('roles.manage-priority') && \Illuminate\Support\Facades\Route::has('roles.hierarchy'));
            $showUserMgmt = $canViewUsers || $canViewRoles || $canViewBranches || $canManageHierarchy;
        @endphp

        @if($showUserMgmt)
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
                <a href="{{ route('roles.index') }}" class="{{ ($isRoleModule && !($isHierarchyPage ?? false)) ? 'active' : '' }}">
                    <span class="label">Manage Roles</span>
                </a>
                @endif
                @can('viewAny', \App\Models\Branch::class)
                <a href="{{ route('branches.index') }}" class="{{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    <span class="label">Manage Branches</span>
                </a>
                @endcan
                @if((auth()->user()->isSuperAdmin() ) || (auth()->user()->hasPermission('roles.manage-priority') && \Illuminate\Support\Facades\Route::has('roles.hierarchy')))
                @php
                    $hierarchyRole = request()->route('role');
                    $hierarchyRoleId = $hierarchyRole instanceof \App\Models\Role ? $hierarchyRole->getKey() : $hierarchyRole;
                    $hierarchyRoleId = $hierarchyRoleId ?: (\App\Models\Role::query()->value('id') ?? 1);
                @endphp
                <a href="{{ route('roles.hierarchy', $hierarchyRoleId) }}" class="{{ ($isHierarchyPage ?? false) ? 'active' : '' }}">
                    <span class="label">Role Hierarchy</span>
                </a>
                @endif
                {{-- Legacy hierarchy link removed --}}
            </div>
        </div>
        @endif
        @endauth

        {{-- Contract Management module (collapsible group) --}}
        @auth
        @php
            $canViewContractTypes = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contract-types.view');
            $canViewContracts = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view');
            $canViewRecipients = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view');
            $showContractMgmt = $canViewContractTypes || $canViewContracts || $canViewRecipients;
            $isContractModule = request()->routeIs('contract-types.*') || request()->routeIs('contracts.*') || request()->routeIs('notification-recipients.*');
        @endphp

        @if($showContractMgmt)
        <div class="nav-group {{ $isContractModule ? 'open' : '' }}" id="nav-contract-mgmt">
            <button
                type="button"
                class="nav-toggle"
                id="nav-contract-mgmt-toggle"
                aria-expanded="{{ $isContractModule ? 'true' : 'false' }}"
                aria-controls="nav-contract-mgmt-submenu"
            >
                <span data-feather="file-text"></span>
                <span class="label">Contract Management</span>
                <span class="nav-toggle-caret" aria-hidden="true" data-feather="chevron-down"></span>
            </button>

            <div class="nav-submenu" id="nav-contract-mgmt-submenu">
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contract-types.view'))
                <a href="{{ route('contract-types.index') }}" class="{{ request()->routeIs('contract-types.*') ? 'active' : '' }}">
                    <span class="label">Contract Types</span>
                </a>
                @endif
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view'))
                <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">
                    <span class="label">Contracts</span>
                </a>
                @endif
                @if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view'))
                <a href="{{ route('notification-recipients.index') }}" class="{{ request()->routeIs('notification-recipients.*') ? 'active' : '' }}">
                    <span class="label">Notification Recipients</span>
                </a>
                @endif
            </div>
        </div>
        @endif
        @endauth
    </nav>

    <div class="sidebar-footer muted">
        <span class="label">© {{ date('Y') }} IOMS</span>
    </div>
</aside>
