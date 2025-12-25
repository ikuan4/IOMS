@extends('layouts.dashboard')

@section('title', 'Manage Roles')

@section('content')
    <div class="header-card">
        <div class="header-left">
            <h2>ROLE MANAGEMENT MODULE</h2>
            <p class="muted">Manage roles, permissions, and access control hierarchy.</p>
        </div>
    </div>

    {{-- Status filter cards --}}
    @php $__u = Auth::user(); @endphp
    @php
        $currentStatus = request('status') ?? 'all';
        $baseParams = ['search' => request('search')];

        $cards = [
            'all' => [
                'label' => 'All Roles',
                'count' => $roles->count(),
            ],
            'active' => [
                'label' => 'Active Roles',
                'count' => $roles->where('deleted_at', null)->where('is_active', true)->count(),
            ],
            'inactive' => [
                'label' => 'Inactive Roles',
                'count' => $roles->where('deleted_at', null)->where('is_active', false)->count(),
            ],
            'deleted' => [
                'label' => 'Deleted Roles',
                'count' => $roles->where('deleted_at', '!=', null)->count(),
            ],
        ];
    @endphp

    <div style="margin-top:12px;">
        <div id="roleCardsContainer" style="
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:16px;
        ">
            @foreach ($cards as $key => $card)
                @php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(
                        array_merge($baseParams, ['status' => $key]),
                        fn($v) => $v !== null && $v !== ''
                    );
                    $cardIndex = array_search($key, array_keys($cards));
                @endphp

                <a href="{{ route('roles.index', $params) }}"
                   class="role-filter-card"
                   style="
                        text-decoration:none;
                        color:inherit;
                   "
                >
                    <div class="card"
                        style="
                            padding:16px 18px;
                            border-radius:12px;
                            border:2px solid {{ $isActiveCard ? '#22c55e' : '#e5e7eb' }};
                            box-shadow:{{ $isActiveCard ? '0 0 0 1px rgba(34,197,94,0.15)' : 'none' }};
                            display:flex;
                            flex-direction:column;
                            justify-content:space-between;
                            height:100%;
                        "
                    >
                        <div style="font-size:14px; opacity:0.8;">
                            {{ $card['label'] }}
                        </div>

                        <div style="margin-top:8px; font-size:24px; font-weight:700;">
                            {{ $card['count'] }}
                        </div>

                        @if($isActiveCard)
                            <div style="margin-top:8px; font-size:12px; color:#16a34a;">
                                Currently applied to table ↓
                            </div>
                        @else
                            <div style="margin-top:8px; font-size:12px; opacity:0.6;">
                                Click to filter table
                            </div>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Toggle button for extra cards --}}
        <div id="toggleRoleCardsContainer" style="display:none; justify-content:flex-end; margin-top:12px;">
            <button
                id="toggleRoleCardsBtn"
                onclick="toggleRoleCards()"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    border:none;
                    background:transparent;
                    color:#22c55e;
                    cursor:pointer;
                    transition:all 0.3s;
                    padding:8px;
                "
                onmouseover="this.style.color='#16a34a'; this.style.transform='scale(1.2)'"
                onmouseout="this.style.color='#22c55e'; this.style.transform='scale(1)'"
            >
                <svg id="toggleRoleIcon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="7 13 12 18 17 13"></polyline>
                    <polyline points="7 6 12 11 17 6"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <script>
        let roleCardsVisible = false;

        function checkRoleCardWrapping() {
            const container = document.getElementById('roleCardsContainer');
            const toggleContainer = document.getElementById('toggleRoleCardsContainer');
            if (!container || !toggleContainer) return;

            const cards = Array.from(container.querySelectorAll('.role-filter-card'));
            if (cards.length === 0) return;

            cards.forEach(card => card.style.display = 'block');
            const firstCardTop = cards[0].getBoundingClientRect().top;
            const wrappedCards = [];

            cards.forEach((card, index) => {
                const cardTop = card.getBoundingClientRect().top;
                if (Math.abs(cardTop - firstCardTop) > 10) {
                    wrappedCards.push(card);
                }
            });

            if (wrappedCards.length > 0) {
                toggleContainer.style.display = 'flex';
                if (!roleCardsVisible) {
                    wrappedCards.forEach(card => card.style.display = 'none');
                }
            } else {
                toggleContainer.style.display = 'none';
                roleCardsVisible = false;
            }
        }

        function toggleRoleCards() {
            const container = document.getElementById('roleCardsContainer');
            const toggleIcon = document.getElementById('toggleRoleIcon');
            if (!container) return;

            const cards = Array.from(container.querySelectorAll('.role-filter-card'));
            const firstCardTop = cards[0].getBoundingClientRect().top;

            roleCardsVisible = !roleCardsVisible;

            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                if (Math.abs(cardTop - firstCardTop) > 10) {
                    card.style.display = roleCardsVisible ? 'block' : 'none';
                }
            });

            if (roleCardsVisible) {
                toggleIcon.innerHTML = '<polyline points="7 11 12 6 17 11"></polyline><polyline points="7 18 12 13 17 18"></polyline>';
            } else {
                toggleIcon.innerHTML = '<polyline points="7 13 12 18 17 13"></polyline><polyline points="7 6 12 11 17 6"></polyline>';
            }
        }

        window.addEventListener('load', checkRoleCardWrapping);
        window.addEventListener('resize', () => {
            roleCardsVisible = false;
            checkRoleCardWrapping();
        });
        setTimeout(checkRoleCardWrapping, 100);
    </script>

    {{-- Search + Add Role --}}
    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px;">
        {{-- Search Form --}}
        <form method="GET" action="{{ route('roles.index') }}" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            {{-- Preserve current status when searching --}}
            <input type="hidden" name="status" value="{{ request('status') ?? 'all' }}">

            <input
                type="text"
                name="search"
                id="roleSearchInput"
                value="{{ request('search') }}"
                placeholder="Search by name, slug, or description..."
                oninput="filterRolesRealtime()"
                style="
                    padding:14px 16px;
                    border-radius:10px;
                    border:1px solid #d0d7e0;
                    min-width:330px;
                    width:330px;
                    font-size:15px;
                "
            />
        </form>

        {{-- Add Role button --}}
        @can('create', \App\Models\Role::class)
        <a
            href="{{ route('roles.create') }}"
            style="
                background:#22c55e;
                color:white;
                padding:10px 24px;
                border-radius:10px;
                font-weight:1000;
                width:220px;
                display:flex;
                justify-content:center;
                align-items:center;
                gap:8px;
                white-space:nowrap;
                text-decoration:none;
            "
        >
            <span data-feather="shield"></span>
            Add Role
        </a>
        @endcan
    </div>

    {{-- Roles table --}}
    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
            <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px;">Role Name</th>
                <th style="padding:8px;">Hierarchy</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px;">Users</th>
                <th style="padding:8px; text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                @php
                    // Hide the Developer role for non-developer and non-super-admin users
                    $currentUser = Auth::user();
                    $isDeveloperRole = strtolower(trim($role->name ?? '')) === 'developer' || strtolower(trim($role->slug ?? '')) === 'developer';
                    $currentIsDeveloper = strtolower(trim($currentUser->role->name ?? '')) === 'developer';
                    $currentIsSuperAdmin = $currentUser->isSuperAdmin();
                @endphp
                @if($isDeveloperRole && !$currentIsDeveloper && !$currentIsSuperAdmin)
                    @continue
                @endif
                @php
                    // Hide user's own role if they have edit permissions (prevents self-modification)
                    $hideOwnRole = $__u && method_exists($__u, 'hasPermission') && $__u->hasPermission('roles.edit') && $__u->role_id === $role->id;
                @endphp
                @if(!$hideOwnRole)
                <tr class="role-table-row"
                    style="border-bottom:1px solid #f3f4f6; height:62px; cursor:pointer; {{ request('role_id') == $role->id ? 'background:#f0fdf4;' : '' }}"
                    data-role-id="{{ $role->id }}"
                    data-name="{{ strtolower($role->name) }}"
                    data-slug="{{ strtolower($role->slug) }}"
                    data-description="{{ strtolower($role->description ?? '') }}"
                    onclick="selectRole({{ $role->id }})">
                    <td style="padding:8px;">
                        <strong>{{ $role->name }}</strong>
                        <br>
                        <span style="font-size:13px; opacity:0.7;">{{ $role->slug }}</span>
                        @if($role->deleted_at && (Auth::user()->isSuperAdmin() || Auth::user()->role?->slug === 'admin'))
                            <br>
                            <span style="font-size:12px; color:#ef4444; opacity:0.8;">
                                <span data-feather="user-x" style="width:12px;height:12px;"></span>
                                Deleted by: {{ $role->deletedBy?->name ?? 'Unknown' }}
                                <span style="margin-left:8px;">{{ $role->deleted_at->format('M j, Y') }}</span>
                            </span>
                        @endif
                    </td>
                    <td style="padding:8px;">
                        @if($role->parent_id)
                            <span style="
                                background:#dbeafe;
                                color:#1e40af;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Child of: {{ $role->parent->name ?? 'N/A' }}</span>
                        @else
                            <span style="
                                background:#f3e8ff;
                                color:#6b21a8;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Parent Role</span>
                        @endif
                    </td>
                    <td style="padding:8px;">
                        @if($role->deleted_at)
                            <span style="
                                background:#fee2e2;
                                color:#b91c1c;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Deleted</span>
                        @elseif($role->is_active)
                            <span style="color:#16a34a;font-weight:600;">Active</span>
                        @else
                            <span style="color:#ea580c;font-weight:600;">Inactive</span>
                        @endif
                    </td>
                    <td style="padding:8px;">
                        <span style="
                            background:#dbeafe;
                            color:#1e40af;
                            padding:4px 12px;
                            border-radius:6px;
                            font-weight:600;
                            font-size:13px;
                        ">
                            {{ $role->users->count() }}
                        </span>
                    </td>
                    <td style="padding:8px; text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                        @if(!$role->isSuperAdmin())
                            @if($role->deleted_at)
                                {{-- Restore button for deleted roles --}}
                                @if($__u && method_exists($__u, 'hasPermission') && $__u->hasPermission('roles.restore'))
                                    <form action="{{ route('roles.restore', $role->id) }}"
                                          method="POST"
                                          style="display:inline-block;"
                                          onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Role', subtitle: 'This will restore the deleted role', message: 'Are you sure you want to restore {{ $role->name }}?', confirmText: 'Restore Role', form: this});">
                                        @csrf

                                        <button type="submit"
                                                style="
                                                    background:#dcfce7;
                                                    color:#16a34a;
                                                    padding:10px 12px;
                                                    border-radius:8px;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    border:none;
                                                    cursor:pointer;
                                                    margin-right:6px;
                                                "
                                                title="Restore role"
                                        >
                                            <span data-feather="refresh-cw"></span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                {{-- Normal actions for non-deleted roles --}}
                                @if(\Illuminate\Support\Facades\Gate::allows('managePermissions', $role))
                                @php
                                    $canEditPermissions = ($__u && method_exists($__u, 'hasPermission') && $__u->hasPermission('permissions.manage') || ($__u && method_exists($__u, 'isSuperAdmin') && $__u->isSuperAdmin())) &&
                                                        ($__u ? $__u->role_id !== $role->id : false); // Cannot edit own role's permissions
                                @endphp
                                <a
                                    href="{{ route('roles.permissions', $role->id) }}"
                                    style="
                                        background:{{ $canEditPermissions ? '#fef3c7' : '#f3f4f6' }};
                                        color:{{ $canEditPermissions ? '#d97706' : '#6b7280' }};
                                        padding:10px 12px;
                                        border-radius:8px;
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        border:none;
                                        cursor:pointer;
                                        margin-right:6px;
                                        text-decoration:none;
                                    "
                                    title="{{ $canEditPermissions ? 'Manage permissions' : 'View permissions (read-only)' }}"
                                >
                                    <span data-feather="{{ $canEditPermissions ? 'key' : 'eye' }}"></span>
                                </a>
                                @endif

                                @if(\Illuminate\Support\Facades\Gate::allows('update', $role))
                                <a
                                    href="{{ route('roles.edit', $role->id) }}"
                                    style="
                                        background:#e0f2fe;
                                        color:#0369a1;
                                        padding:10px 12px;
                                        border-radius:8px;
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        border:none;
                                        cursor:pointer;
                                        margin-right:6px;
                                        text-decoration:none;
                                    "
                                    title="Edit role"
                                >
                                    <span data-feather="edit"></span>
                                </a>
                                @endif

                                @if(\Illuminate\Support\Facades\Gate::allows('delete', $role))
                                @if($role->users->count() === 0)
                                    <form action="{{ route('roles.destroy', $role->id) }}"
                                          method="POST"
                                          style="display:inline-block; margin-right:9px;"
                                          onsubmit="event.preventDefault(); showConfirmModal({type: 'delete', title: 'Delete Role', subtitle: 'This will soft delete the role', message: 'Are you sure you want to delete {{ $role->name }}? This action can be reversed later.', confirmText: 'Delete Role', form: this});">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                style="
                                                    background:#fee2e2;
                                                    color:#b91c1c;
                                                    padding:10px 12px;
                                                    border-radius:8px;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    border:none;
                                                    cursor:pointer;
                                                "
                                                title="Soft delete role"
                                        >
                                            <span data-feather="trash-2"></span>
                                        </button>
                                    </form>
                                @endif
                                @else
                                    {{-- Placeholder to maintain spacing for delete icon position (increased by 55%) --}}
                                    <div style="display:inline-block; width:48px; height:36px; margin-right:9px;"></div>
                                @endif
                            @endif
                        @else
                            <button
                                style="
                                    background:#f3f4f6;
                                    color:#9ca3af;
                                    padding:10px 12px;
                                    border-radius:8px;
                                    display:inline-flex;
                                    align-items:center;
                                    justify-content:center;
                                    border:none;
                                    cursor:not-allowed;
                                "
                                title="This role cannot be modified"
                                disabled
                            >
                                <span data-feather="lock"></span>
                            </button>
                        @endif
                    </td>
                </tr>
                @endif
            @empty
                <tr>
                    <td colspan="5" style="padding:12px;">No roles found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{-- Users by Role Section --}}
    <div class="card" style="margin-top:12px;">
        <div style="padding:16px 18px; border-bottom:1px solid #e5e7eb;">
            <h5 style="margin:0; font-size:16px; font-weight:600;">Users by Role</h5>
            <p style="margin:4px 0 0 0; font-size:13px; opacity:0.7;">Click a role above to view its assigned users</p>
        </div>
        <div id="users-by-role-container">
            <div style="padding:48px; text-align:center; opacity:0.6;">
                <span data-feather="users" style="width:48px; height:48px; margin-bottom:16px;"></span>
                <p>Select a role from the table above to view its users</p>
            </div>
        </div>
    </div>

    @include('partials.confirmation-modal')

    <script>
        function filterRolesRealtime() {
            const searchInput = document.getElementById('roleSearchInput');
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.role-table-row');

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const slug = row.getAttribute('data-slug') || '';
                const description = row.getAttribute('data-description') || '';

                const matches = name.includes(searchTerm) ||
                               slug.includes(searchTerm) ||
                               description.includes(searchTerm);

                row.style.display = (matches || searchTerm === '') ? '' : 'none';
            });
        }

        function selectRole(roleId) {
            const usersContainer = document.getElementById('users-by-role-container');

            // Show loading state
            usersContainer.innerHTML = `
                <div style="padding:48px; text-align:center;">
                    <div style="display:inline-block; width:32px; height:32px; border:3px solid #e5e7eb; border-top-color:#22c55e; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                    <p style="margin-top:16px; opacity:0.6;">Loading users...</p>
                </div>
            `;

            // Fetch users by role
            fetch(`{{ route('roles.index') }}?role_id=${roleId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(users => {
                if (users.length === 0) {
                    usersContainer.innerHTML = `
                        <div style="padding:48px; text-align:center; opacity:0.6;">
                            <span data-feather="users" style="width:48px; height:48px; margin-bottom:16px;"></span>
                            <p>No users assigned to this role</p>
                        </div>
                    `;
                    feather.replace();
                } else {
                    let tableHtml = `
                        <div style="overflow-x:auto;">
                            <table style="width:100%; border-collapse:collapse;">
                                <thead>
                                    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                                        <th style="padding:8px;">Name</th>
                                        <th style="padding:8px;">Email</th>
                                        <th style="padding:8px;">Status</th>
                                        <th style="padding:8px;">All Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    users.forEach(user => {
                        const statusBadge = user.active
                            ? '<span style="color:#16a34a;font-weight:600;">Active</span>'
                            : '<span style="color:#dc2626;font-weight:600;">Inactive</span>';

                        const roles = user.custom_roles
                            ? user.custom_roles.map(r => `<span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;margin-right:4px;">${r.name}</span>`).join('')
                            : '<span style="opacity:0.5;">None</span>';

                        tableHtml += `
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:8px;">${user.name}</td>
                                <td style="padding:8px;">${user.email}</td>
                                <td style="padding:8px;">${statusBadge}</td>
                                <td style="padding:8px;">${roles}</td>
                            </tr>
                        `;
                    });

                    tableHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;

                    usersContainer.innerHTML = tableHtml;
                }
            })
            .catch(error => {
                console.error('Error fetching users:', error);
                usersContainer.innerHTML = `
                    <div style="padding:48px; text-align:center; color:#dc2626;">
                        <span data-feather="alert-triangle" style="width:48px; height:48px; margin-bottom:16px;"></span>
                        <p>Error loading users. Please try again.</p>
                    </div>
                `;
                feather.replace();
            });
        }
    </script>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .header-card .header-left h2 {
                font-size: 20px;
            }

            /* Make filter cards stack on mobile */
            .card {
                min-width: 100% !important;
            }

            /* Make action buttons smaller */
            table td > div[style*="gap:6px"] a,
            table td > div[style*="gap:6px"] button {
                padding: 8px 12px !important;
                font-size: 13px !important;
            }

            /* Hide table on very small screens, or make horizontal scroll */
            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }
        }
    </style>
@endsection
