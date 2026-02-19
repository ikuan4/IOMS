<?php $__env->startSection('title', 'Manage Roles'); ?>

<?php $__env->startSection('content'); ?>
    <div class="header-card">
        <div class="header-left">
            <h2>ROLE MANAGEMENT MODULE</h2>
            <p class="muted">Manage roles, permissions, and access control hierarchy.</p>
        </div>
    </div>

    
    <?php
        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->user();
    ?>
    <?php
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
    ?>

    <div style="margin-top:12px;">
        <div id="roleCardsContainer" style="
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
            gap:16px;
        ">
            <?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $card): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $isActiveCard = $currentStatus === $key;
                    $params = array_filter(
                        array_merge($baseParams, ['status' => $key]),
                        fn($v) => $v !== null && $v !== ''
                    );
                    $cardIndex = array_search($key, array_keys($cards));
                ?>

                <a href="<?php echo e(route('roles.index', $params)); ?>"
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
                            border:2px solid <?php echo e($isActiveCard ? '#22c55e' : '#e5e7eb'); ?>;
                            box-shadow:<?php echo e($isActiveCard ? '0 0 0 1px rgba(34,197,94,0.15)' : 'none'); ?>;
                            display:flex;
                            flex-direction:column;
                            justify-content:space-between;
                            height:100%;
                        "
                    >
                        <div style="font-size:14px; opacity:0.8;">
                            <?php echo e($card['label']); ?>

                        </div>

                        <div style="margin-top:8px; font-size:24px; font-weight:700;">
                            <?php echo e($card['count']); ?>

                        </div>

                        <?php if($isActiveCard): ?>
                            <div style="margin-top:8px; font-size:12px; color:#16a34a;">
                                Currently applied to table ↓
                            </div>
                        <?php else: ?>
                            <div style="margin-top:8px; font-size:12px; opacity:0.6;">
                                Click to filter table
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        
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

    
    <div class="header-right" style="display:flex;gap:12px;align-items:flex-end; margin-top:16px;">
        
        <form method="GET" action="<?php echo e(route('roles.index')); ?>" id="roleSearchForm" style="display:flex;gap:12px;align-items:flex-end; flex-wrap:wrap;">
            
            <input type="hidden" name="status" value="<?php echo e(request('status') ?? 'all'); ?>">
            <input type="hidden" name="per_page" id="roleSearchPerPage" value="<?php echo e(request()->query('per_page', 10)); ?>">

            <input
                type="text"
                name="search"
                id="roleSearchInput"
                value="<?php echo e(request('search')); ?>"
                placeholder="Search by name, slug, or description..."
                oninput="debouncedRoleSearch()"
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

        
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\Role::class)): ?>
        <a
            href="<?php echo e(route('roles.create')); ?>"
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
        <?php endif; ?>
    </div>

    
    <div class="card" style="margin-top:12px; overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
            <tr style="text-align:left; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px;">Role Name</th>
                <th style="padding:8px;">Branch</th>
                <th style="padding:8px;">Hierarchy</th>
                <th style="padding:8px;">Status</th>
                <th style="padding:8px;">Users</th>
                <th style="padding:8px; text-align:right;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    // Hide the Developer role for non-developer and non-super-admin users
                    $isDeveloperRole = method_exists($role, 'isSuperAdmin') && $role->isSuperAdmin();
                    $currentIsSuperAdmin = $currentUser ? $currentUser->isSuperAdmin() : false;
                ?>
                <?php if($isDeveloperRole && !$currentIsSuperAdmin): ?>
                    <?php continue; ?>
                <?php endif; ?>
                <?php
                    // Hide user's own role if they have edit permissions (prevents self-modification)
                    $hideOwnRole = $currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('roles.edit') && $currentUser->role_id === $role->id;
                ?>
                <?php if(!$hideOwnRole): ?>
                <tr class="role-table-row <?php echo e(request('role_id') == $role->id ? 'role-selected' : ''); ?>"
                    style="border-bottom:1px solid #f3f4f6; height:62px; cursor:pointer;"
                    data-role-id="<?php echo e($role->id); ?>"
                    data-name="<?php echo e(strtolower($role->name)); ?>"
                    data-slug="<?php echo e(strtolower($role->slug)); ?>"
                    data-description="<?php echo e(strtolower($role->description ?? '')); ?>"
                    onclick="selectRole(<?php echo e($role->id); ?>)">
                    <td style="padding:8px;">
                        <strong><?php echo e($role->name); ?></strong>
                        <br>
                        <span style="font-size:13px; opacity:0.7;"><?php echo e($role->slug); ?></span>
                        <?php if($role->deleted_at && ($currentUser && ($currentUser->isSuperAdmin() || (method_exists($currentUser, 'isAdmin') && $currentUser->isAdmin())))): ?>
                            <br>
                            <span style="font-size:12px; color:#ef4444; opacity:0.8;">
                                <span data-feather="user-x" style="width:12px;height:12px;"></span>
                                Deleted by: <?php echo e($role->deletedBy?->name ?? 'Unknown'); ?>

                                <span style="margin-left:8px;"><?php echo e($role->deleted_at->format('M j, Y')); ?></span>
                            </span>
                        <?php endif; ?>
                    </td>

                    <td style="padding:8px;">
                        <?php if($role->branch): ?>
                            <span style="background:#f1f5f9;color:#0f172a;padding:6px 10px;border-radius:6px;font-size:13px;font-weight:700;"><?php echo e($role->branch->name); ?></span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px;">
                        <?php if($role->parent_id): ?>
                            <span style="
                                background:#dbeafe;
                                color:#1e40af;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Child of: <?php echo e($role->parent->name ?? 'N/A'); ?></span>
                        <?php else: ?>
                            <span style="
                                background:#f3e8ff;
                                color:#6b21a8;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Parent Role</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:8px;">
                        <?php if($role->deleted_at): ?>
                            <span style="
                                background:#fee2e2;
                                color:#b91c1c;
                                padding:4px 12px;
                                border-radius:6px;
                                font-weight:600;
                                font-size:13px;
                            ">Deleted</span>
                        <?php elseif($role->is_active): ?>
                            <span style="color:#16a34a;font-weight:600;">Active</span>
                        <?php else: ?>
                            <span style="color:#ea580c;font-weight:600;">Inactive</span>
                        <?php endif; ?>
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
                            <?php echo e($role->userCount()); ?>

                        </span>
                    </td>
                    <td style="padding:8px; text-align:right; white-space:nowrap;" onclick="event.stopPropagation();">
                        <?php if(!$role->isSuperAdmin()): ?>
                            <?php if($role->deleted_at): ?>
                                
                                <?php if($currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('roles.restore')): ?>
                                    <form action="<?php echo e(route('roles.restore', $role->id)); ?>"
                                          method="POST"
                                          style="display:inline-block;"
                                          onsubmit="event.preventDefault(); showConfirmModal({type: 'restore', title: 'Restore Role', subtitle: 'This will restore the deleted role', message: 'Are you sure you want to restore <?php echo e($role->name); ?>?', confirmText: 'Restore Role', form: this});">
                                        <?php echo csrf_field(); ?>

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
                                <?php endif; ?>
                            <?php else: ?>
                                
                                <?php if(\Illuminate\Support\Facades\Gate::allows('managePermissions', $role)): ?>
                                <?php
                                    $canEditPermissions = (($currentUser && method_exists($currentUser, 'hasPermission') && $currentUser->hasPermission('permissions.manage')) || ($currentUser && method_exists($currentUser, 'isSuperAdmin') && $currentUser->isSuperAdmin())) &&
                                                        ($currentUser ? $currentUser->role_id !== $role->id : false); // Cannot edit own role's permissions
                                ?>
                                <a
                                    href="<?php echo e(route('roles.permissions', $role->id)); ?>"
                                    style="
                                        background:<?php echo e($canEditPermissions ? '#fef3c7' : '#f3f4f6'); ?>;
                                        color:<?php echo e($canEditPermissions ? '#d97706' : '#6b7280'); ?>;
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
                                    title="<?php echo e($canEditPermissions ? 'Manage permissions' : 'View permissions (read-only)'); ?>"
                                >
                                    <span data-feather="<?php echo e($canEditPermissions ? 'key' : 'eye'); ?>"></span>
                                </a>
                                <?php endif; ?>

                                <?php if(\Illuminate\Support\Facades\Gate::allows('update', $role)): ?>
                                <a
                                    href="<?php echo e(route('roles.edit', $role->id)); ?>"
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
                                <?php endif; ?>

                                <?php if(\Illuminate\Support\Facades\Gate::allows('delete', $role)): ?>
                                    <form action="<?php echo e(route('roles.destroy', $role->id)); ?>"
                                          method="POST"
                                          style="display:inline-block; margin-right:9px;"
                                          onsubmit="event.preventDefault(); checkRoleMappedUsers(this, '<?php echo e($role->id); ?>', '<?php echo e(addslashes($role->name)); ?>');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>

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
                                <?php else: ?>
                                    
                                    <div style="display:inline-block; width:48px; height:36px; margin-right:9px;"></div>
                                <?php endif; ?>

                                <?php if(\Illuminate\Support\Facades\Gate::allows('view', $role)): ?>
                                    <a href="<?php echo e(route('roles.show', $role->id)); ?>" title="View role" style="background:#f8fafc;color:#0f172a;padding:10px 12px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;border:none;cursor:pointer;margin-left:6px;text-decoration:none;"><span data-feather="eye"></span></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
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
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="6" style="padding:12px;">No roles found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <div style="margin-top:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:160px; color:var(--muted,#6b7280); font-weight:600;">
                Total Roles: <?php echo e($roles->total()); ?>

            </div>

            <div style="flex:1; display:flex; justify-content:center;">
                <?php
                    $current = $roles->currentPage();
                    $last = $roles->lastPage();
                    $start = max(1, $current - 2);
                    $end = min($last, $start + 4);
                    if ($end - $start < 4) { $start = max(1, $end - 4); }
                    $baseParams = request()->except(['page']);
                ?>

                <nav aria-label="Pagination" style="display:inline-flex;align-items:center;gap:6px;background:var(--card);padding:6px 10px;border-radius:8px;">
                    
                    <?php $firstColor = $current > 1 ? '#2563eb' : '#000'; ?>
                    <?php if($current > 1): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => 1]))); ?>" aria-label="First page" style="padding:6px 10px;border-radius:6px;background:transparent;color:<?php echo e($firstColor); ?>;text-decoration:none;font-weight:800;font-size:14px;">&laquo;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:6px 10px;color:<?php echo e($firstColor); ?>;font-weight:800;font-size:14px;">&laquo;</span>
                    <?php endif; ?>

                    
                    <?php $prevColor = $current > 1 ? '#2563eb' : '#000'; ?>
                    <?php if($current > 1): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current - 1]))); ?>" aria-label="Previous page" style="padding:8px 12px;border-radius:6px;background:transparent;color:<?php echo e($prevColor); ?>;text-decoration:none;font-weight:800;font-size:15px;">&lt;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:8px 12px;color:<?php echo e($prevColor); ?>;font-weight:800;font-size:15px;">&lt;</span>
                    <?php endif; ?>

                    
                    <?php for($p = $start; $p <= $end; $p++): ?>
                        <?php if($p == $current): ?>
                            <span aria-current="page" style="padding:9px 14px;border-radius:8px;background:#f3f4f6;color:#374151;font-weight:800;border:1px solid #e5e7eb;font-size:15px;"><?php echo e($p); ?></span>
                        <?php else: ?>
                            <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $p]))); ?>" style="padding:8px 12px;border-radius:6px;background:transparent;color:var(--text);text-decoration:none;font-weight:800;font-size:14px;" onmouseover="this.style.border='1px solid #22c55e';this.style.background='rgba(34,197,94,0.06)';" onmouseout="this.style.border='none';this.style.background='transparent';"><?php echo e($p); ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    
                    <?php $nextColor = $current < $last ? '#2563eb' : '#000'; ?>
                    <?php if($current < $last): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $current + 1]))); ?>" aria-label="Next page" style="padding:8px 12px;border-radius:6px;background:transparent;color:<?php echo e($nextColor); ?>;text-decoration:none;font-weight:800;font-size:15px;">&gt;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:8px 12px;color:<?php echo e($nextColor); ?>;font-weight:800;font-size:15px;">&gt;</span>
                    <?php endif; ?>

                    
                    <?php $lastColor = $current < $last ? '#2563eb' : '#000'; ?>
                    <?php if($current < $last): ?>
                        <a href="<?php echo e(url()->current() . '?' . http_build_query(array_merge($baseParams, ['page' => $last]))); ?>" aria-label="Last page" style="padding:6px 10px;border-radius:6px;background:transparent;color:<?php echo e($lastColor); ?>;text-decoration:none;font-weight:800;font-size:14px;">&raquo;</a>
                    <?php else: ?>
                        <span aria-hidden="true" style="padding:6px 10px;color:<?php echo e($lastColor); ?>;font-weight:800;font-size:14px;">&raquo;</span>
                    <?php endif; ?>
                </nav>

    <script>
        async function checkRoleMappedUsers(form, roleId, roleName) {
            // Use the new dependency check endpoint
            const checkUrl = `<?php echo e(url('/')); ?>/roles/${roleId}/check-delete-dependencies`;

            showConfirmModal({
                type: 'delete',
                title: 'Delete Role',
                subtitle: `Checking if "${roleName}" can be deleted...`,
                message: 'Verifying dependencies...',
                confirmText: 'Delete Role',
                checkDependenciesUrl: checkUrl,
                form: form
            });
        }
    </script>
            </div>

            <div style="flex:1; min-width:180px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                <?php $currentPerPage = (int) request()->query('per_page', $roles->perPage() ?? 10); ?>
                <form method="GET" action="<?php echo e(url()->current()); ?>" id="rolePerPageForm" style="display:flex;align-items:center;gap:8px;">
                    <?php $__currentLoopData = request()->except(['per_page','page']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>" />
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <label for="role_per_page" style="font-size:13px;color:var(--muted,#6b7280);">Show per Page:</label>
                    <select name="per_page" id="role_per_page" onchange="document.getElementById('rolePerPageForm').submit()" style="padding:8px;border-radius:8px;border:1px solid var(--muted,#e5e7eb);background:var(--card);color:var(--text,inherit);">
                        <?php $__currentLoopData = [5,10,15,20,30]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e($currentPerPage == $opt ? 'selected' : ''); ?>><?php echo e($opt); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </form>
            </div>
        </div>

    
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

    <script>
        // AJAX search for roles
        let __roleSearchTimer = null;
        function debouncedRoleSearch() {
            clearTimeout(__roleSearchTimer);
            __roleSearchTimer = setTimeout(() => { ajaxFetchRoles(1); }, 300);
        }

        function ajaxFetchRoles(page = 1) {
            const form = document.getElementById('roleSearchForm');
            const params = new URLSearchParams(new FormData(form));
            const perPageSelect = document.getElementById('role_per_page') || document.getElementById('per_page');
            if (perPageSelect) params.set('per_page', perPageSelect.value);
            params.set('page', page);
            const url = `${location.pathname}?${params.toString()}`;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => {
                    if (!r.ok) throw new Error('Network error');
                    return r.text();
                })
                .then(html => {
                    const wrapper = document.querySelector('.card[style*="margin-top:12px; overflow-x:auto;"]');
                    if (wrapper) {
                        wrapper.outerHTML = `<div class="card" style="margin-top:12px; overflow-x:auto;">${html}</div>`;
                        bindPaginationLinks();
                        bindRoleSelectionHandlers();
                        try { if (window.feather && typeof window.feather.replace === 'function') window.feather.replace(); } catch (err) { }
                    }
                }).catch(e => {
                    console.error(e);
                });
        }

        function bindPaginationLinks(){
            const table = document.querySelector('.card[style*="margin-top:12px; overflow-x:auto;"] table');
            if (!table) return;
            const wrapper = table.closest('.card');
            if (!wrapper) return;
            wrapper.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href) return;
                try {
                    const url = new URL(href, location.origin);
                    if (url.searchParams.has('page')) {
                        a.addEventListener('click', function(ev){ ev.preventDefault(); ajaxFetchRoles(url.searchParams.get('page')); });
                    }
                } catch(e) {}
            });
            // Also handle per-page select
            const perPageSelect = wrapper.querySelector('select[name="per_page"]');
            if (perPageSelect) {
                perPageSelect.addEventListener('change', function(){ ajaxFetchRoles(1); });
            }
        }

        function bindRoleSelectionHandlers() {
            const roleRows = document.querySelectorAll('.role-table-row');
            roleRows.forEach(row => {
                row.addEventListener('click', function() {
                    const roleId = this.dataset.roleId;
                    if (roleId) selectRole(roleId);
                });
            });
        }

        function selectRole(roleId) {
                const usersContainer = document.getElementById('users-by-role-container');

                // Visually mark selected role row
                document.querySelectorAll('.role-table-row').forEach(r => r.classList.remove('role-selected'));
                const selectedRow = document.querySelector('.role-table-row[data-role-id="' + roleId + '"]');
                if (selectedRow) {
                    selectedRow.classList.add('role-selected');
                    try { selectedRow.scrollIntoView({behavior: 'smooth', block: 'center'}); } catch (e) {}
                }

                // Update URL query param without reloading
                try {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('role_id', roleId);
                    history.replaceState({}, '', newUrl.toString());
                } catch (e) {}

                // Show loading state
                usersContainer.innerHTML = `
                    <div style="padding:48px; text-align:center;">
                        <div style="display:inline-block; width:32px; height:32px; border:3px solid #e5e7eb; border-top-color:#22c55e; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                        <p style="margin-top:16px; opacity:0.6;">Loading users...</p>
                    </div>
                `;

                // Fetch users by role (cache-busted) and be resilient to response content-type
                fetch(`<?php echo e(route('roles.index')); ?>?role_id=${roleId}&_=${Date.now()}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (response.status === 403 || response.status === 401) {
                        throw new Error('unauthorized');
                    }
                    const ct = (response.headers.get('content-type') || '').toLowerCase();
                    if (ct.includes('application/json')) return response.json();
                    return response.text().then(txt => {
                        try { return JSON.parse(txt); } catch (e) { throw new Error('invalid-json'); }
                    });
                })
                .then(users => {
                    if (!users || users.length === 0) {
                        usersContainer.innerHTML = `
                            <div style="padding:48px; text-align:center; opacity:0.6;">
                                <span data-feather="users" style="width:48px; height:48px; margin-bottom:16px;"></span>
                                <p>No users assigned to this role</p>
                            </div>
                        `;
                        if (window.feather) feather.replace();
                        return;
                    }

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

                        const roles = user.custom_roles && user.custom_roles.length > 0
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
                    if (window.feather) feather.replace();
                })
                .catch(err => {
                    console.error('Error fetching users:', err);
                    if (err.message === 'unauthorized') {
                        usersContainer.innerHTML = `
                            <div style="padding:48px; text-align:center; color:#dc2626;">
                                <span data-feather="lock" style="width:48px; height:48px; margin-bottom:16px;"></span>
                                <p>You do not have permission to view users for this role.</p>
                            </div>
                        `;
                    } else {
                        usersContainer.innerHTML = `
                            <div style="padding:48px; text-align:center; color:#dc2626;">
                                <span data-feather="alert-triangle" style="width:48px; height:48px; margin-bottom:16px;"></span>
                                <p>Error loading users. Please try again.</p>
                            </div>
                        `;
                    }
                    if (window.feather) feather.replace();
                });
        }
    </script>

    <style>
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .role-selected {
            background: var(--accent-bg, #f0fdf4) !important;
            transition: box-shadow 0.15s ease-in-out;
            box-shadow: 0 2px 6px rgba(34,197,94,0.08);
        }

        [data-theme="dark"] .role-selected {
            background: rgba(34, 197, 94, 0.15) !important;
            box-shadow: 0 2px 6px rgba(34,197,94,0.25);
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dashboard', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\xampp\htdocs\IOMS\resources\views/roles/index.blade.php ENDPATH**/ ?>