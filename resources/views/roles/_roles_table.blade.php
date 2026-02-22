@php
    /** @var \App\Models\User|null $currentUser */
    $currentUser = auth()->user();
@endphp

<table style="width:100%; border-collapse:collapse;">
    <thead>
    <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
        <th style="padding:8px;">Role Name</th>
        <th style="padding:8px;">Type</th>
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
            $isDeveloperRole = method_exists($role, 'isSuperAdmin') && $role->isSuperAdmin();
            $currentIsSuperAdmin = $currentUser ? $currentUser->isSuperAdmin() : false;
        @endphp
        @if($isDeveloperRole && !$currentIsSuperAdmin)
            @continue
        @endif
        @php
            $currentUserRole = $currentUser ? $currentUser->effectiveRole() : null;
            // Hide user's own role if they have edit permissions (prevents self-modification)
            $hideOwnRole = $currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('roles.edit') && $currentUserRole && $currentUserRole->id === $role->id;
        @endphp
        @if(!$hideOwnRole)
        <tr class="role-table-row {{ request('role_id') == $role->id ? 'role-selected' : '' }}"
            style="border-bottom:1px solid #f3f4f6; height:62px; cursor:pointer;"
            data-role-id="{{ $role->id }}"
            data-name="{{ strtolower($role->name) }}"
            data-slug="{{ strtolower($role->slug) }}"
            data-description="{{ strtolower($role->description ?? '') }}"
            onclick="selectRole({{ $role->id }})">
            <td style="padding:8px;">
                <strong>{{ $role->name }}</strong>
                <br>
                <span style="font-size:13px; opacity:0.7;">{{ $role->slug }}</span>
                @if($role->deleted_at && ($currentUser && ($currentUser->isSuperAdmin() || (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin()))))
                    <br>
                    <span style="font-size:12px; color:#ef4444; opacity:0.8;">
                        <span data-feather="user-x" style="width:12px;height:12px;"></span>
                        Deleted by: {{ $role->deletedBy?->name ?? 'Unknown' }}
                        <span style="margin-left:8px;">{{ $role->deleted_at->format('M j, Y') }}</span>
                    </span>
                @endif
            </td>

            <td style="padding:8px;">
                @if($role->is_global)
                    <span style="display:inline-flex;align-items:center;gap:4px;background:#dbeafe;color:#1e40af;padding:6px 10px;border-radius:6px;font-size:13px;font-weight:700;">
                        <span data-feather="globe" style="width:14px;height:14px;"></span>
                        Global
                    </span>
                @else
                    <span style="display:inline-flex;align-items:center;gap:4px;background:#dcfce7;color:#166534;padding:6px 10px;border-radius:6px;font-size:13px;font-weight:700;">
                        <span data-feather="map-pin" style="width:14px;height:14px;"></span>
                        Branch
                    </span>
                @endif
            </td>
            <td style="padding:8px;">
                @if($role->parent_id)
                    <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:6px;font-weight:600;font-size:13px;">Child of: {{ $role->parent->name ?? 'N/A' }}</span>
                @else
                    <span style="background:#f3e8ff;color:#6b21a8;padding:4px 12px;border-radius:6px;font-weight:600;font-size:13px;">Parent Role</span>
                @endif
            </td>
            <td style="padding:8px;">
                @if($role->deleted_at)
                    <span style="background:#fee2e2;color:#b91c1c;padding:4px 12px;border-radius:6px;font-weight:600;font-size:13px;">Deleted</span>
                @elseif($role->is_active)
                    <span style="color:#16a34a;font-weight:600;">Active</span>
                @else
                    <span style="color:#ea580c;font-weight:600;">Inactive</span>
                @endif
            </td>
            <td style="padding:8px;">
                <span style="background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:6px;font-weight:600;font-size:13px;">
                    @if($role->is_global)
                        {{ $role->globalUsers()->count() }}
                    @else
                        {{ $role->branchUsers()->count() }}
                    @endif
                </span>
            </td>
            <td style="padding:8px; text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                @if(!$role->isSuperAdmin())
                    @if($role->deleted_at)
                        {{-- Restore button for deleted roles --}}
                        @if($currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('roles.restore'))
                            <form action="{{ route('roles.restore', $role->id) }}"
                                  method="POST"
                                  style="display:inline-block;"
                                  onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Role', subtitle: 'This will restore the deleted role', message: 'Are you sure you want to restore {{ $role->name }}?', confirmText: 'Restore Role', form: this});">
                                @csrf

                                <button type="submit"
                                        style="background:#dcfce7;color:#16a34a;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;"
                                        title="Restore role">
                                    <span data-feather="refresh-cw"></span>
                                </button>
                            </form>
                        @endif
                    @else
                        {{-- Normal actions for non-deleted roles --}}
                        @if(\Illuminate\Support\Facades\Gate::allows('managePermissions', $role))
                        @php
                            $canEditPermissions = (($currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('permissions.manage')) || ($currentUser && method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin())) &&
                                                ($currentUserRole && $currentUserRole->id !== $role->id); // Cannot edit own role's permissions
                        @endphp
                        <a
                            href="{{ route('roles.permissions', $role->id) }}"
                            style="background:{{ $canEditPermissions ? '#fef3c7' : '#f3f4f6' }};color:{{ $canEditPermissions ? '#d97706' : '#6b7280' }};padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;"
                            title="{{ $canEditPermissions ? 'Manage permissions' : 'View permissions (read-only)' }}">
                            <span data-feather="{{ $canEditPermissions ? 'key' : 'eye' }}"></span>
                        </a>
                        @endif

                        @if(\Illuminate\Support\Facades\Gate::allows('update', $role))
                        <a
                            href="{{ route('roles.edit', $role->id) }}"
                            style="background:#e0f2fe;color:#0369a1;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-right:6px;text-decoration:none;"
                            title="Edit role">
                            <span data-feather="edit"></span>
                        </a>
                        @endif

                        @if(\Illuminate\Support\Facades\Gate::allows('delete', $role))
                            <form action="{{ route('roles.destroy', $role->id) }}"
                                  method="POST"
                                  style="display:inline-block; margin-right:9px;"
                                  onsubmit="event.preventDefault(); checkRoleMappedUsers(this, '{{ $role->id }}', '{{ addslashes($role->name) }}');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        style="background:#fee2e2;color:#b91c1c;padding:10px 12px;border-radius:8px;display:flex;align-items:center;justify-content:center;border:none;cursor:pointer;"
                                        title="Soft delete role">
                                    <span data-feather="trash-2"></span>
                                </button>
                            </form>
                        @else
                            <div style="display:inline-block; width:48px; height:36px; margin-right:9px;"></div>
                        @endif

                        @if(\Illuminate\Support\Facades\Gate::allows('view', $role))
                            <a href="{{ route('roles.show', $role->id) }}" title="View role" style="background:#f8fafc;color:#0f172a;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-left:6px;text-decoration:none;"><span data-feather="eye"></span></a>
                        @endif
                    @endif
                @else
                    <button style="background:#f3f4f6;color:#9ca3af;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:not-allowed;" title="This role cannot be modified" disabled>
                        <span data-feather="lock"></span>
                    </button>
                @endif
            </td>
        </tr>
        @endif
    @empty
        <tr>
            <td colspan="6" style="padding:12px;">No roles found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
    <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
        Total Roles: {{ $roles->total() }}
    </div>

    <div style="flex:1; display:flex; justify-content:center;">
        @php
            $current = $roles->currentPage();
            $last = $roles->lastPage();
            $start = max(1, $current - 2);
            $end = min($last, $start + 4);
            if ($end - $start < 4) { $start = max(1, $end - 4); }
            $baseParams = request()->except(['page']);
        @endphp

        <nav aria-label="Pagination" style="display:inline-flex;align-items:center;gap:6px;background:var(--card);padding:6px 10px;border-radius:8px;">
            @php $firstColor = $current > 1 ? '#2563eb' : '#000'; @endphp
            @if($current > 1)
                <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => 1])) }}" aria-label="First page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $firstColor }};text-decoration:none;font-weight:800;font-size:14px;">&laquo;</a>
            @else
                <span aria-hidden="true" style="padding:6px 10px;color:{{ $firstColor }};font-weight:800;font-size:14px;">&laquo;</span>
            @endif

            @php $prevColor = $current > 1 ? '#2563eb' : '#000'; @endphp
            @if($current > 1)
                <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current - 1])) }}" aria-label="Previous page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $prevColor }};text-decoration:none;font-weight:800;font-size:15px;">&lt;</a>
            @else
                <span aria-hidden="true" style="padding:8px 12px;color:{{ $prevColor }};font-weight:800;font-size:15px;">&lt;</span>
            @endif

            @for($p = $start; $p <= $end; $p++)
                @if($p == $current)
                    <span aria-current="page" style="padding:9px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:800;border:1px solid #e5e7eb;font-size:15px;">{{ $p }}</span>
                @else
                    <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $p])) }}" style="padding:8px 12px;border-radius:6px;background:transparent;color:var(--text);text-decoration:none;font-weight:800;font-size:14px;" onmouseover="this.style.border='1px solid #22c55e';this.style.background='rgba(34,197,94,0.06)';" onmouseout="this.style.border='none';this.style.background='transparent';">{{ $p }}</a>
                @endif
            @endfor

            @php $nextColor = $current < $last ? '#2563eb' : '#000'; @endphp
            @if($current < $last)
                <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current + 1])) }}" aria-label="Next page" style="padding:8px 12px;border-radius:6px;background:transparent;color:{{ $nextColor }};text-decoration:none;font-weight:800;font-size:15px;">&gt;</a>
            @else
                <span aria-hidden="true" style="padding:8px 12px;color:{{ $nextColor }};font-weight:800;font-size:15px;">&gt;</span>
            @endif

            @php $lastColor = $current < $last ? '#2563eb' : '#000'; @endphp
            @if($current < $last)
                <a href="{{ url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $last])) }}" aria-label="Last page" style="padding:6px 10px;border-radius:6px;background:transparent;color:{{ $lastColor }};text-decoration:none;font-weight:800;font-size:14px;">&raquo;</a>
            @else
                <span aria-hidden="true" style="padding:6px 10px;color:{{ $lastColor }};font-weight:800;font-size:14px;">&raquo;</span>
            @endif
        </nav>
    </div>

    <div style="flex:1; min-width:180px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
        @php $currentPerPage = (int) request()->query('per_page', $roles->perPage() ?? 10); @endphp
        <form method="GET" action="{{ url()->current() }}" id="rolePerPageForm" style="display:flex;align-items:center;gap:8px;">
            @foreach(request()->except(['per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}" />
            @endforeach
            <label for="role_per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
            <select name="per_page" id="role_per_page" onchange="document.getElementById('rolePerPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                @foreach([5,10,15,20,30] as $opt)
                    <option value="{{ $opt }}" {{ $currentPerPage == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
