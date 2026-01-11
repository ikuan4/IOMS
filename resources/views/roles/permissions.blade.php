@extends('layouts.dashboard')

@section('title', 'Manage Permissions')

@section('content')
    @php
        /** @var \App\Models\User|null $__u */
        $__u = Auth::user();
        $canEdit = $__u && (
            (method_exists($__u, 'hasPermission') && $__u->hasPermission('permissions.manage')) ||
            (method_exists($__u, 'isSuperAdmin') && $__u->isSuperAdmin())
        ) && ($__u ? $__u->role_id !== $role->id : false); // Cannot edit own role's permissions
    @endphp

    <div class="header-card">
        <div class="header-left">
            <h2>Manage Permissions: {{ $role->name }}</h2>
            <p class="muted">Control what actions users with this role can perform.</p>
        </div>
    </div>

    {{-- Information Box --}}
    <div style="
        background:var(--card);
        border:1px solid rgba(59, 130, 246, 0.3);
        border-radius:10px;
        padding:16px;
        margin-top:12px;
    ">
        <div style="display:flex;align-items:start;gap:12px;">
            <span data-feather="info" style="color:var(--accent);flex-shrink:0;margin-top:2px;"></span>
            <div>
                <h6 style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:var(--text);">Permission Structure</h6>
                <p style="margin:0;font-size:13px;color:var(--text);line-height:1.5;opacity:0.9;">
                    Permissions are organized by module groups. {{ $canEdit ? 'Toggle permissions for' : 'View permissions for' }} <strong>{{ $role->name }}</strong> below.
                    @if($canEdit)
                        Changes are saved when you click "Save Permissions".
                        @if(!$isSuperAdmin)
                            <br><strong>Note:</strong> You can only grant permissions that you currently have.
                        @endif
                    @else
                        <br><strong>Note:</strong> You have view-only access. Contact an administrator to modify permissions.
                    @endif
                </p>
            </div>
        </div>
    </div>

    <form action="{{ route('roles.permissions.update', $role->id) }}" method="POST" id="permissions-form" {{ !$canEdit ? 'class=view-only style=opacity:0.6;' : '' }}>
        @csrf

        <div class="card" style="margin-top:12px;">
            <div style="padding:16px 18px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                <h5 style="margin:0; font-size:16px; font-weight:600;">{{ $canEdit ? 'Assign' : 'View' }} Permissions</h5>
                @if($canEdit)
                <label class="toggle-switch" style="user-select:none;">
                    <input
                        type="checkbox"
                        id="master-toggle"
                        {{ !$canEdit ? 'disabled' : '' }}
                    >
                    <span class="toggle-slider"></span>
                    <span style="font-size:13px;font-weight:600;">Select All Permissions</span>
                </label>
                @else
                <span style="color:#6b7280;font-size:13px;font-weight:600;">View Only Mode</span>
                @endif
            </div>

            <div style="padding:16px;">
                @php
                    // Reorder modules: existing modules first, then future modules
                    $existingModules = ['dashboard', 'users', 'roles', 'branches', 'permissions'];
                    $futureModules = ['contract-types', 'contracts', 'notifications'];

                    $orderedPermissions = collect();

                    // Add existing modules first
                    foreach($existingModules as $moduleName) {
                        if($permissions->has($moduleName)) {
                            $orderedPermissions->put($moduleName, $permissions->get($moduleName));
                        }
                    }

                    // Add future modules
                    foreach($futureModules as $moduleName) {
                        if($permissions->has($moduleName)) {
                            $orderedPermissions->put($moduleName, $permissions->get($moduleName));
                        }
                    }

                    // Add any remaining modules not in either list
                    foreach($permissions as $group => $perms) {
                        if(!$orderedPermissions->has($group)) {
                            $orderedPermissions->put($group, $perms);
                        }
                    }
                @endphp

                @foreach($orderedPermissions as $group => $perms)
                    <div class="permission-group" style="margin-bottom:24px;">
                        {{-- Module Header --}}
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;padding-bottom:8px;border-bottom:2px solid #3b82f6;">
                            <div onclick="toggleModuleCollapse('{{ $group }}')" style="display:flex;align-items:center;gap:8px;cursor:pointer;flex:1;">
                                <span
                                    id="module-chevron-{{ $group }}"
                                    data-feather="chevron-down"
                                    style="width:20px;height:20px;color:#3b82f6;transition:transform 0.3s;transform:rotate(-90deg);"
                                ></span>
                                <h5 style="margin:0;font-size:15px;font-weight:600;color:#3b82f6;">
                                    @php
                                        $iconMap = [
                                            'dashboard' => 'home',
                                            'users' => 'users',
                                            'roles' => 'shield',
                                            'branches' => 'git-branch',
                                            'permissions' => 'key',
                                            'contract-types' => 'list',
                                            'contracts' => 'file-text',
                                            'notifications' => 'bell',
                                        ];
                                        $icon = $iconMap[$group] ?? 'folder';

                                        // Define which modules are not yet implemented
                                        $futureModules = ['contract-types', 'contracts', 'notifications'];
                                        $isFutureModule = in_array($group, $futureModules);
                                    @endphp
                                    <span data-feather="{{ $icon }}" style="width:18px;height:18px;"></span>
                                    {{ ucwords(str_replace('-', ' ', $group)) }}
                                    @if($isFutureModule)
                                        <span style=\"
                                            font-size:10px;
                                            font-weight:700;
                                            color:#f59e0b;
                                            background:rgba(245, 158, 11, 0.1);
                                            padding:2px 8px;
                                            border-radius:12px;
                                            margin-left:8px;
                                            text-transform:uppercase;
                                            letter-spacing:0.5px;
                                        \"></span>
                                    @endif
                                </h5>
                            </div>
                            <label class="toggle-switch" style="user-select:none;" onclick="event.stopPropagation();">
                                @php
                                    $groupPermIds = collect($perms)->pluck('id')->toArray();
                                    $hasAnyChecked = count(array_intersect($groupPermIds, $rolePermissions)) > 0;
                                @endphp
                                <input
                                    class="module-toggle"
                                    type="checkbox"
                                    id="module-{{ $group }}"
                                    data-group="{{ $group }}"
                                    {{ $hasAnyChecked ? 'checked' : '' }}
                                    {{ !$canEdit ? 'disabled' : '' }}
                                >
                                <span class="toggle-slider"></span>
                                <span style="font-size:13px;font-weight:600;">Enable Module</span>
                            </label>
                        </div>

                        {{-- Permissions Grid (Collapsible) --}}
                        <div id="module-content-{{ $group }}" style="overflow:hidden;transition:max-height 0.3s ease;max-height:0px;">
                            @if($group === 'contracts')
                                {{-- Special layout for contracts with separator --}}
                                @php
                                    $mainPerms = collect($perms)->filter(function($p) {
                                        return !str_starts_with($p->slug, 'contracts.versions.') && $p->slug !== 'contracts.export';
                                    });
                                    $additionalPerms = collect($perms)->filter(function($p) {
                                        return str_starts_with($p->slug, 'contracts.versions.') || $p->slug === 'contracts.export';
                                    });
                                @endphp

                                {{-- Main permissions --}}
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-top:12px;" data-module-group="{{ $group }}">
                                @foreach($mainPerms as $permission)
                                    <div style="
                                        background:white;
                                        border:1px solid #e5e7eb;
                                        border-radius:8px;
                                        padding:12px;
                                        transition:all 0.2s;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:12px;
                                    " class="permission-card{{ !$hasAnyChecked ? ' disabled' : '' }}" data-module="{{ $group }}">
                                        <div>
                                            <strong style="font-size:14px;display:block;margin-bottom:4px;">{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <span style="font-size:12px;color:#6b7280;">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                        <label class="toggle-switch" style="flex-shrink:0;">
                                            <input
                                                class="permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                                data-group="{{ $group }}"
                                                data-permission="{{ $permission->slug }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                                </div>

                                {{-- Separator --}}
                                @if($additionalPerms->count() > 0)
                                <div style="margin:16px 0;border-top:1px dashed #d1d5db;"></div>

                                {{-- Additional permissions --}}
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;" data-module-group="{{ $group }}">
                                @foreach($additionalPerms as $permission)
                                    <div style="
                                        background:white;
                                        border:1px solid #e5e7eb;
                                        border-radius:8px;
                                        padding:12px;
                                        transition:all 0.2s;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:12px;
                                    " class="permission-card{{ !$hasAnyChecked ? ' disabled' : '' }}" data-module="{{ $group }}">
                                        <div>
                                            <strong style="font-size:14px;display:block;margin-bottom:4px;">{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <span style="font-size:12px;color:#6b7280;">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                        <label class="toggle-switch" style="flex-shrink:0;">
                                            <input
                                                class="permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                                data-group="{{ $group }}"
                                                data-permission="{{ $permission->slug }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                                </div>
                                @endif
                            @elseif($group === 'roles')
                                {{-- Special layout for roles with separator --}}
                                @php
                                    $mainPerms = collect($perms)->filter(function($p) {
                                        return $p->slug !== 'roles.manage-priority';
                                    });
                                    $hierarchyPerms = collect($perms)->filter(function($p) {
                                        return $p->slug === 'roles.manage-priority';
                                    });
                                @endphp

                                {{-- Main role permissions --}}
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-top:12px;" data-module-group="{{ $group }}">
                                @foreach($mainPerms as $permission)
                                    <div style="
                                        background:white;
                                        border:1px solid #e5e7eb;
                                        border-radius:8px;
                                        padding:12px;
                                        transition:all 0.2s;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:12px;
                                    " class="permission-card{{ !$hasAnyChecked ? ' disabled' : '' }}" data-module="{{ $group }}">
                                        <div>
                                            <strong style="font-size:14px;display:block;margin-bottom:4px;">{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <span style="font-size:12px;color:#6b7280;">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                        <label class="toggle-switch" style="flex-shrink:0;">
                                            <input
                                                class="permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                                data-group="{{ $group }}"
                                                data-permission="{{ $permission->slug }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                                </div>

                                {{-- Separator --}}
                                @if($hierarchyPerms->count() > 0)
                                <div style="margin:16px 0;border-top:1px dashed #d1d5db;"></div>

                                {{-- Hierarchy management permissions --}}
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;" data-module-group="{{ $group }}">
                                @foreach($hierarchyPerms as $permission)
                                    <div style="
                                        background:white;
                                        border:1px solid #e5e7eb;
                                        border-radius:8px;
                                        padding:12px;
                                        transition:all 0.2s;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:12px;
                                    " class="permission-card{{ !$hasAnyChecked ? ' disabled' : '' }}" data-module="{{ $group }}">
                                        <div>
                                            <strong style="font-size:14px;display:block;margin-bottom:4px;">{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <span style="font-size:12px;color:#6b7280;">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                        <label class="toggle-switch" style="flex-shrink:0;">
                                            <input
                                                class="permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                                data-group="{{ $group }}"
                                                data-permission="{{ $permission->slug }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                                </div>
                                @endif
                            @else
                                {{-- Standard layout for other modules --}}
                                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px;margin-top:12px;" data-module-group="{{ $group }}">
                                @foreach($perms as $permission)
                                    <div style="
                                        background:white;
                                        border:1px solid #e5e7eb;
                                        border-radius:8px;
                                        padding:12px;
                                        transition:all 0.2s;
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:12px;
                                    " class="permission-card{{ !$hasAnyChecked ? ' disabled' : '' }}" data-module="{{ $group }}">
                                        <div>
                                            <strong style="font-size:14px;display:block;margin-bottom:4px;">{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <span style="font-size:12px;color:#6b7280;">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                        <label class="toggle-switch" style="flex-shrink:0;">
                                            <input
                                                class="permission-checkbox"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                id="permission-{{ $permission->id }}"
                                                data-group="{{ $group }}"
                                                data-permission="{{ $permission->slug }}"
                                                {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                            >
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Action Buttons --}}
            <div id="action-buttons-card" style="padding:16px; border-top:1px solid rgba(0,0,0,0.1); background:var(--card); display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between;">
                <a
                    href="{{ route('roles.index') }}"
                    style="
                        background:var(--muted);
                        color:var(--text);
                        padding:14px 24px;
                        border-radius:10px;
                        font-weight:1000;
                        font-size:15px;
                        text-decoration:none;
                        display:flex;
                        align-items:center;
                        gap:8px;
                    "
                >
                    <span data-feather="arrow-left"></span> Back to Roles
                </a>
                @if($canEdit)
                <button
                    type="submit"
                    form="permissions-form"
                    style="
                        background:#22c55e;
                        color:white;
                        padding:14px 24px;
                        border-radius:10px;
                        font-weight:1000;
                        font-size:15px;
                        border:none;
                        display:flex;
                        align-items:center;
                        gap:8px;
                        cursor:pointer;
                    "
                >
                    <span data-feather="save"></span> Save Permissions
                </button>
                @endif
            </div>
        </div>
    </form>

    {{-- Permission Summary --}}
    <div class="card" style="margin-top:12px;">
        <div style="padding:16px;">
            <h6 style="margin:0 0 16px 0;font-size:15px;font-weight:600;">
                <span data-feather="bar-chart-2" style="width:18px;height:18px;"></span>
                Permission Summary
            </h6>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;text-align:center;">
                <div>
                    <h3 style="margin:0;font-size:28px;font-weight:bold;color:#3b82f6;" id="total-permissions">{{ count($rolePermissions) }}</h3>
                    <p class="muted" style="margin:4px 0 0 0;font-size:13px;">Permissions Assigned</p>
                </div>
                <div>
                    <h3 style="margin:0;font-size:28px;font-weight:bold;color:#6b7280;">{{ array_sum(array_map('count', $permissions->toArray())) }}</h3>
                    <p class="muted" style="margin:4px 0 0 0;font-size:13px;">Total Available</p>
                </div>
                <div>
                    <h3 style="margin:0;font-size:28px;font-weight:bold;color:#0ea5e9;" id="coverage-percentage">
                        {{ array_sum(array_map('count', $permissions->toArray())) > 0 ? round((count($rolePermissions) / array_sum(array_map('count', $permissions->toArray()))) * 100) : 0 }}%
                    </h3>
                    <p class="muted" style="margin:4px 0 0 0;font-size:13px;">Coverage</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
/* Theme-aware card and background colors */
.permission-card {
    background: var(--card) !important;
    border-color: rgba(0,0,0,0.1) !important;
}

[data-theme="dark"] .permission-card {
    border-color: rgba(255,255,255,0.15) !important;
}

#action-buttons-card {
    background: var(--card) !important;
    border-top-color: rgba(0,0,0,0.1) !important;
}

[data-theme="dark"] #action-buttons-card {
    border-top-color: rgba(255,255,255,0.1) !important;
}

/* Toggle Switch Styles */
.toggle-switch {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.toggle-switch input {
    display: none;
}

.toggle-slider {
    width: 44px;
    height: 24px;
    border-radius: 999px;
    background: #e5e7eb;
    position: relative;
    transition: background 0.2s ease;
}

[data-theme="dark"] .toggle-slider {
    background: #4b5563;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    background: #ffffff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    transition: transform 0.2s ease;
}

.toggle-switch input:checked + .toggle-slider {
    background: #22c55e;
}

.toggle-switch input:checked + .toggle-slider::before {
    transform: translateX(20px);
}

.permission-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
    border-color: var(--accent) !important;
}

[data-theme="dark"] .permission-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.permission-card.disabled {
    opacity: 0.5;
    background: var(--bg) !important;
}

.permission-card.disabled .toggle-slider {
    background: #d1d5db !important;
}

[data-theme="dark"] .permission-card.disabled .toggle-slider {
    background: #374151 !important;
}

/* Disabled checkboxes won't change state but can still receive clicks */
.toggle-switch input[disabled] + .toggle-slider {
    cursor: not-allowed;
}

/* View-only mode: disable form controls but allow module expansion */
#permissions-form.view-only .toggle-switch {
    pointer-events: none;
}

#permissions-form.view-only .permission-card .toggle-switch {
    pointer-events: none;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .header-card .header-left h2 {
        font-size: 20px;
    }

    .card > div[style*="display:flex"] {
        flex-direction: column;
        align-items: stretch !important;
    }

    .card > div[style*="display:flex"] > div {
        width: 100%;
    }

    .card > div[style*="display:flex"] button,
    .card > div[style*="display:flex"] a {
        justify-content: center;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Toggle module collapse/expand
function toggleModuleCollapse(group) {
    const content = document.getElementById('module-content-' + group);
    const chevron = document.getElementById('module-chevron-' + group);

    if (content.style.maxHeight && content.style.maxHeight !== '0px') {
        // Collapse
        content.style.maxHeight = '0px';
        chevron.style.transform = 'rotate(-90deg)';
    } else {
        // Expand
        content.style.maxHeight = content.scrollHeight + 'px';
        chevron.style.transform = 'rotate(0deg)';
    }

    // Refresh feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const masterToggle = document.getElementById('master-toggle');
    const moduleToggles = document.querySelectorAll('.module-toggle');
    const permissionCheckboxes = document.querySelectorAll('.permission-checkbox');
    const totalPermissionsEl = document.getElementById('total-permissions');
    const coveragePercentageEl = document.getElementById('coverage-percentage');

    // Handle view-only mode
    const canEdit = {{ $canEdit ? 'true' : 'false' }};
    if (!canEdit) {
        // Disable all checkboxes and form interactions
        if (masterToggle) masterToggle.disabled = true;
        moduleToggles.forEach(toggle => toggle.disabled = true);
        permissionCheckboxes.forEach(checkbox => checkbox.disabled = true);

        // Disable form submission
        const form = document.getElementById('permissions-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                return false;
            });
        }

        // Add visual indicator to permission cards but keep module headers clickable
        document.querySelectorAll('.permission-card').forEach(card => {
            card.classList.add('disabled');
        });
    }

    // Update summary
    function updateSummary() {
        const checked = document.querySelectorAll('.permission-checkbox:checked').length;
        const total = permissionCheckboxes.length;
        const percentage = total > 0 ? Math.round((checked / total) * 100) : 0;

        totalPermissionsEl.textContent = checked;
        coveragePercentageEl.textContent = percentage + '%';
    }

    // Master toggle - Select/Deselect all permissions
    masterToggle.addEventListener('change', function() {
        const isChecked = this.checked;

        moduleToggles.forEach(toggle => {
            toggle.checked = isChecked;
            const group = toggle.dataset.group;
            const groupCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            const groupCards = document.querySelectorAll(`.permission-card[data-module="${group}"]`);

            if (isChecked) {
                // Select all
                groupCards.forEach(card => card.classList.remove('disabled'));
                groupCheckboxes.forEach(checkbox => {
                    checkbox.disabled = false;
                    checkbox.removeAttribute('disabled');
                    checkbox.checked = true;
                });
            } else {
                // Deselect all
                groupCards.forEach(card => card.classList.add('disabled'));
                groupCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.disabled = true;
                    checkbox.setAttribute('disabled', 'disabled');
                });
            }
        });

        updateSummary();
    });

    // Update module toggle state based on individual permissions
    function updateModuleToggleState(group) {
        const moduleToggle = document.querySelector(`.module-toggle[data-group="${group}"]`);
        const groupCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
        const hasAnyChecked = Array.from(groupCheckboxes).some(cb => cb.checked);

        // Auto-check module toggle if any permission is checked
        if (hasAnyChecked && !moduleToggle.checked) {
            moduleToggle.checked = true;
        }
        // Auto-uncheck module toggle if no permissions are checked
        else if (!hasAnyChecked && moduleToggle.checked) {
            moduleToggle.checked = false;
        }

        // Update visual state using pointer-events instead of disabled
        const groupCards = document.querySelectorAll(`.permission-card[data-module="${group}"]`);
        if (moduleToggle.checked) {
            groupCards.forEach(card => {
                card.classList.remove('disabled');
                card.style.pointerEvents = 'auto';
                card.style.opacity = '1';
            });
        } else {
            groupCards.forEach(card => {
                card.classList.add('disabled');
                card.style.pointerEvents = 'none';
                card.style.opacity = '0.5';
            });
        }
    }

    // Module toggle functionality - enables/disables entire module
    moduleToggles.forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            console.log('Module toggle changed:', this.dataset.group, 'checked:', this.checked);
            const group = this.dataset.group;
            const groupCheckboxes = document.querySelectorAll(`.permission-checkbox[data-group="${group}"]`);
            const groupCards = document.querySelectorAll(`.permission-card[data-module="${group}"]`);

            if (this.checked) {
                // Enable module - allow interaction (but don't auto-check all)
                console.log('Enabling module:', group);
                groupCards.forEach(card => {
                    card.classList.remove('disabled');
                    card.style.pointerEvents = 'auto';
                    card.style.opacity = '1';
                });
            } else {
                // Disable module - prevent interaction and uncheck all
                console.log('Disabling module:', group);
                groupCards.forEach(card => {
                    card.classList.add('disabled');
                    card.style.pointerEvents = 'none';
                    card.style.opacity = '0.5';
                });
                groupCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }

            updateSummary();
        });

        // Initialize state
        updateModuleToggleState(toggle.dataset.group);
    });

    // Update individual checkbox changes (only when module is enabled)
    permissionCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            console.log('Permission checkbox changed:', this.id, 'checked:', this.checked);
            const group = this.dataset.group;
            handlePermissionDependencies(this);
            // Enforce versions view dependency UI state on any change
            updateVersionCrudDisabled();
            updateModuleToggleState(group);
            updateSummary();
        });

        // Also add click listener to debug
        checkbox.addEventListener('click', function(e) {
            console.log('Permission checkbox clicked:', this.id, 'disabled:', this.disabled);
        });
    });

    // Handle permission dependencies
    function handlePermissionDependencies(checkbox) {
        const permissionSlug = checkbox.dataset.permission;
        const isChecked = checkbox.checked;

        // Handle contract version dependencies
        if (permissionSlug.startsWith('contracts.versions.')) {
            const action = permissionSlug.split('.').pop();
            const viewVersionSlug = 'contracts.versions.view';

            // Rule: When enabling any version CRUD action, auto-enable view versions
            if (isChecked && ['create', 'edit', 'delete', 'restore'].includes(action)) {
                const viewVersionCheckbox = document.querySelector(`input[data-permission="${viewVersionSlug}"]`);
                if (viewVersionCheckbox && !viewVersionCheckbox.checked) {
                    console.log(`Auto-enabling ${viewVersionSlug} because ${permissionSlug} was enabled`);
                    viewVersionCheckbox.checked = true;
                }
            }

            // Rule: When disabling view versions, auto-disable all version CRUD actions
            if (!isChecked && action === 'view') {
                ['create', 'edit', 'delete', 'restore'].forEach(crudAction => {
                    const crudSlug = 'contracts.versions.' + crudAction;
                    const crudCheckbox = document.querySelector(`input[data-permission="${crudSlug}"]`);
                    if (crudCheckbox && crudCheckbox.checked) {
                        console.log(`Auto-disabling ${crudSlug} because ${viewVersionSlug} was disabled`);
                        crudCheckbox.checked = false;
                    }
                });
            }
        }

        // Extract module and action from slug (e.g., "users.create" -> module: "users", action: "create")
        const parts = permissionSlug.split('.');
        if (parts.length !== 2) return;

        const [module, action] = parts;
        const viewSlug = module + '.view';

        // Rule 1: When enabling create/edit/delete, auto-enable view
        if (isChecked && ['create', 'edit', 'delete'].includes(action)) {
            const viewCheckbox = document.querySelector(`input[data-permission="${viewSlug}"]`);
            if (viewCheckbox && !viewCheckbox.checked) {
                console.log(`Auto-enabling ${viewSlug} because ${permissionSlug} was enabled`);
                viewCheckbox.checked = true;
            }
        }

        // Rule 2: When disabling view, auto-disable all CRUD permissions
        if (!isChecked && action === 'view') {
            ['create', 'edit', 'delete'].forEach(crudAction => {
                const crudSlug = module + '.' + crudAction;
                const crudCheckbox = document.querySelector(`input[data-permission="${crudSlug}"]`);
                if (crudCheckbox && crudCheckbox.checked) {
                    console.log(`Auto-disabling ${crudSlug} because ${viewSlug} was disabled`);
                    crudCheckbox.checked = false;
                }
            });
        }
    }

    // Disable/enable version CRUD toggles based on View Contract Versions
    function updateVersionCrudDisabled() {
        const viewCheckbox = document.querySelector('input[data-permission="contracts.versions.view"]');
        const crudActions = ['create', 'edit', 'delete', 'restore'];
        const shouldDisable = !(viewCheckbox && viewCheckbox.checked);

        crudActions.forEach(action => {
            const cb = document.querySelector(`input[data-permission="contracts.versions.${action}"]`);
            if (!cb) return;
            const card = cb.closest('.permission-card');
            if (shouldDisable) {
                cb.checked = false; // keep consistent with rule
                cb.setAttribute('disabled', 'disabled');
                if (card) { card.style.opacity = '0.6'; card.style.pointerEvents = 'none'; }
            } else {
                cb.removeAttribute('disabled');
                if (card) { card.style.opacity = '1'; card.style.pointerEvents = 'auto'; }
            }
        });
    }

    // Initial enforcement for version dependencies
    updateVersionCrudDisabled();

    // Debug: Add click listeners to all toggle labels
    document.querySelectorAll('.toggle-switch').forEach(label => {
        label.addEventListener('click', function(e) {
            console.log('Toggle switch clicked:', e.target);
        });
    });

    // Before form submission, log what's being submitted
    const form = document.getElementById('permissions-form');
    form.addEventListener('submit', function(e) {
        console.log('Form submitting...');

        // Log what's being submitted
        const formData = new FormData(form);
        const permissions = formData.getAll('permissions[]');
        console.log('Submitting permissions:', permissions);
        console.log('Number of permissions:', permissions.length);
    });

    // Initialize summary
    updateSummary();

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Sticky action buttons card behavior
    const actionCard = document.getElementById('action-buttons-card');
    if (actionCard) {
        let cardOriginalTop, cardHeight, cardWidth, cardLeft;

        // Create a placeholder to maintain layout when card becomes fixed
        const placeholder = document.createElement('div');
        placeholder.style.display = 'none';
        actionCard.parentNode.insertBefore(placeholder, actionCard.nextSibling);

        // Store original styles
        const originalStyles = {
            position: actionCard.style.position,
            bottom: actionCard.style.bottom,
            left: actionCard.style.left,
            right: actionCard.style.right,
            width: actionCard.style.width,
            zIndex: actionCard.style.zIndex,
            marginTop: actionCard.style.marginTop,
            borderTop: actionCard.style.borderTop
        };

        function updateDimensions() {
            const cardRect = actionCard.getBoundingClientRect();
            cardOriginalTop = cardRect.top + window.scrollY;
            cardHeight = cardRect.height;
            cardWidth = cardRect.width;
            cardLeft = cardRect.left;
            placeholder.style.height = cardHeight + 'px';
        }

        // Initial dimensions
        updateDimensions();

        function handleScroll() {
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const viewportHeight = window.innerHeight;

            // Get fresh dimensions if card is not fixed
            if (actionCard.style.position !== 'fixed') {
                const cardRect = actionCard.getBoundingClientRect();
                cardOriginalTop = cardRect.top + window.scrollY;
                cardHeight = cardRect.height;
                cardWidth = cardRect.width;
                cardLeft = cardRect.left;
            }

            const cardBottom = cardOriginalTop + cardHeight;

            // Check if the card's original position would be below viewport
            if (scrollTop + viewportHeight < cardBottom) {
                // Card is scrolling out of view - make it sticky at bottom
                actionCard.style.position = 'fixed';
                actionCard.style.bottom = '0';
                actionCard.style.left = cardLeft + 'px';
                actionCard.style.width = cardWidth + 'px';
                actionCard.style.zIndex = '1000';
                actionCard.style.marginTop = '0';
                actionCard.style.right = 'auto';
                actionCard.style.borderTop = 'none';
                placeholder.style.display = 'block';
            } else {
                // Card has reached its original position - make it static again
                actionCard.style.position = originalStyles.position;
                actionCard.style.bottom = originalStyles.bottom;
                actionCard.style.left = originalStyles.left;
                actionCard.style.right = originalStyles.right;
                actionCard.style.width = originalStyles.width;
                actionCard.style.zIndex = originalStyles.zIndex;
                actionCard.style.marginTop = originalStyles.marginTop;
                actionCard.style.borderTop = originalStyles.borderTop;
                placeholder.style.display = 'none';
            }
        }

        // Handle window resize to recalculate positions
        function handleResize() {
            // Reset to original position first
            actionCard.style.position = originalStyles.position;
            actionCard.style.bottom = originalStyles.bottom;
            actionCard.style.left = originalStyles.left;
            actionCard.style.right = originalStyles.right;
            actionCard.style.width = originalStyles.width;
            actionCard.style.zIndex = originalStyles.zIndex;
            actionCard.style.marginTop = originalStyles.marginTop;
            placeholder.style.display = 'none';

            // Update dimensions
            setTimeout(() => {
                updateDimensions();
                handleScroll();
            }, 10);
        }

        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', handleResize);

        // Initial check
        handleScroll();
    }
});
</script>
@endpush
