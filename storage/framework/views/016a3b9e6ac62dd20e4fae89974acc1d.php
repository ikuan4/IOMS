<aside class="sidebar" id="sidebar" aria-hidden="false">
    <div class="brand">
        <div class="brand-logo">I</div>
        <div class="brand-text">
            <h1>Modules</h1>
        </div>
    </div>

    <nav class="nav" aria-label="Main navigation">
        <?php if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('dashboard.view'))): ?>
        <a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
            <span data-feather="home"></span>
            <span class="label">Dashboard</span>
        </a>
        <?php endif; ?>

        <?php
            $isRoleModule = request()->routeIs('roles.*');
            $isUserModule = request()->routeIs('users.*');
            $isBranchModule = request()->routeIs('branches.*');
            $isUserMgmtModule = $isRoleModule || $isUserModule || $isBranchModule;
            $isHierarchyPage = request()->routeIs('roles.hierarchy') || request()->routeIs('roles.hierarchy.*');
        ?>

        
        <?php if(auth()->guard()->check()): ?>
            <?php
                $canViewAuditLogs = auth()->user()->isSuperAdmin() && \Illuminate\Support\Facades\Route::has('audit-logs.index');
                $isAuditLogsModule = request()->routeIs('audit-logs.*') || request()->is('audit-logs*');
            ?>

            <?php if($canViewAuditLogs): ?>
                <a href="<?php echo e(route('audit-logs.index')); ?>" class="<?php echo e($isAuditLogsModule ? 'active' : ''); ?>">
                    <span data-feather="activity"></span>
                    <span class="label">Audit Logs</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        
        <?php if(auth()->guard()->check()): ?>
        <?php
            $canViewUsers = auth()->user()->can('viewAny', \App\Models\User::class);
            $canViewRoles = auth()->user()->can('viewAny', \App\Models\Role::class);
            $canViewBranches = auth()->user()->can('viewAny', \App\Models\Branch::class);
            $canManageHierarchy = (auth()->user()->isSuperAdmin()) || (auth()->user()->hasPermission('roles.manage-priority') && \Illuminate\Support\Facades\Route::has('roles.hierarchy'));
            $showUserMgmt = $canViewUsers || $canViewRoles || $canViewBranches || $canManageHierarchy;
        ?>

        <?php if($showUserMgmt): ?>
        <div class="nav-group <?php echo e($isUserMgmtModule ? 'open' : ''); ?>" id="nav-user-mgmt">
            <button
                type="button"
                class="nav-toggle"
                id="nav-user-mgmt-toggle"
                aria-expanded="<?php echo e($isUserMgmtModule ? 'true' : 'false'); ?>"
                aria-controls="nav-user-mgmt-submenu"
            >
                <span data-feather="users"></span>
                <span class="label">User Management</span>
                <span class="nav-toggle-caret" aria-hidden="true" data-feather="chevron-down"></span>
            </button>

            <div class="nav-submenu" id="nav-user-mgmt-submenu">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\User::class)): ?>
                <a href="<?php echo e(route('users.index')); ?>" class="<?php echo e($isUserModule ? 'active' : ''); ?>">
                    <span class="label">Manage Users</span>
                </a>
                <?php endif; ?>
                <?php if(auth()->user()->can('viewAny', \App\Models\Role::class)): ?>
                <a href="<?php echo e(route('roles.index')); ?>" class="<?php echo e(($isRoleModule && !($isHierarchyPage ?? false)) ? 'active' : ''); ?>">
                    <span class="label">Manage Roles</span>
                </a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', \App\Models\Branch::class)): ?>
                <a href="<?php echo e(route('branches.index')); ?>" class="<?php echo e(request()->routeIs('branches.*') ? 'active' : ''); ?>">
                    <span class="label">Manage Branches</span>
                </a>
                <?php endif; ?>
                <?php if((auth()->user()->isSuperAdmin() ) || (auth()->user()->hasPermission('roles.manage-priority') && \Illuminate\Support\Facades\Route::has('roles.hierarchy'))): ?>
                <?php
                    $hierarchyRole = request()->route('role');
                    $hierarchyRoleId = $hierarchyRole instanceof \App\Models\Role ? $hierarchyRole->getKey() : $hierarchyRole;
                    $hierarchyRoleId = $hierarchyRoleId ?: (\App\Models\Role::query()->value('id') ?? 1);
                ?>
                <a href="<?php echo e(route('roles.hierarchy', $hierarchyRoleId)); ?>" class="<?php echo e(($isHierarchyPage ?? false) ? 'active' : ''); ?>">
                    <span class="label">Role Hierarchy</span>
                </a>
                <?php endif; ?>
                
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        
        <?php if(auth()->guard()->check()): ?>
        <?php
            $canViewContractTypes = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contract-types.view');
            $canViewContracts = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view');
            $canViewRecipients = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view');
            $showContractMgmt = $canViewContractTypes || $canViewContracts || $canViewRecipients;
            $isContractModule = request()->routeIs('contract-types.*') || request()->routeIs('contracts.*') || request()->routeIs('notification-recipients.*');
        ?>

        <?php if($showContractMgmt): ?>
        <div class="nav-group <?php echo e($isContractModule ? 'open' : ''); ?>" id="nav-contract-mgmt">
            <button
                type="button"
                class="nav-toggle"
                id="nav-contract-mgmt-toggle"
                aria-expanded="<?php echo e($isContractModule ? 'true' : 'false'); ?>"
                aria-controls="nav-contract-mgmt-submenu"
            >
                <span data-feather="file-text"></span>
                <span class="label">Contract Management</span>
                <span class="nav-toggle-caret" aria-hidden="true" data-feather="chevron-down"></span>
            </button>

            <div class="nav-submenu" id="nav-contract-mgmt-submenu">
                <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contract-types.view')): ?>
                <a href="<?php echo e(route('contract-types.index')); ?>" class="<?php echo e(request()->routeIs('contract-types.*') ? 'active' : ''); ?>">
                    <span class="label">Contract Types</span>
                </a>
                <?php endif; ?>
                <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('contracts.view')): ?>
                <a href="<?php echo e(route('contracts.index')); ?>" class="<?php echo e(request()->routeIs('contracts.*') ? 'active' : ''); ?>">
                    <span class="label">Contracts</span>
                </a>
                <?php endif; ?>
                <?php if(auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('notification-recipients.view')): ?>
                <a href="<?php echo e(route('notification-recipients.index')); ?>" class="<?php echo e(request()->routeIs('notification-recipients.*') ? 'active' : ''); ?>">
                    <span class="label">Notification Recipients</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        
        <?php if(auth()->guard()->check()): ?>
        <?php
            $canViewTickets = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('tickets.view');
            $canViewPendingTickets = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('tickets.pending.view');
            $canViewTicketTypes = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('ticket-types.view');
            $canViewTicketModules = auth()->user()->isSuperAdmin() || auth()->user()->hasPermission('ticket-modules.view');
            $ticketsIndexRouteExists = \Illuminate\Support\Facades\Route::has('tickets.index');
            $ticketTypesIndexRouteExists = \Illuminate\Support\Facades\Route::has('ticket-types.index');
            $ticketModulesIndexRouteExists = \Illuminate\Support\Facades\Route::has('ticket-modules.index');
            $showTicketMgmt = $canViewTickets || $canViewPendingTickets || $canViewTicketTypes || $canViewTicketModules;
            $ticketsIndexUrl = $ticketsIndexRouteExists ? route('tickets.index') : null;
            $ticketTypesIndexUrl = $ticketTypesIndexRouteExists ? route('ticket-types.index') : null;
            $ticketModulesIndexUrl = $ticketModulesIndexRouteExists ? route('ticket-modules.index') : null;
            $isTicketModule = request()->routeIs('tickets.*') || request()->routeIs('ticket-types.*') || request()->routeIs('ticket-modules.*')
                || request()->is('tickets*') || request()->is('ticket-types*') || request()->is('ticket-modules*');
        ?>

        <?php if($showTicketMgmt): ?>
        <div class="nav-group <?php echo e($isTicketModule ? 'open' : ''); ?>" id="nav-ticket-mgmt">
            <button
                type="button"
                class="nav-toggle"
                id="nav-ticket-mgmt-toggle"
                aria-expanded="<?php echo e($isTicketModule ? 'true' : 'false'); ?>"
                aria-controls="nav-ticket-mgmt-submenu"
            >
                <span data-feather="inbox"></span>
                <span class="label">Ticket Management</span>
                <span class="nav-toggle-caret" aria-hidden="true" data-feather="chevron-down"></span>
            </button>

            <div class="nav-submenu" id="nav-ticket-mgmt-submenu">
                <?php if($canViewTicketModules): ?>
                    <?php if($ticketModulesIndexUrl): ?>
                    <a href="<?php echo e($ticketModulesIndexUrl); ?>" class="<?php echo e(request()->routeIs('ticket-modules.*') || request()->is('ticket-modules*') ? 'active' : ''); ?>">
                        <span class="label">Ticket Modules</span>
                    </a>
                    <?php else: ?>
                    <a href="#" class="disabled" aria-disabled="true" tabindex="-1" onclick="return false;">
                        <span class="label">Ticket Modules</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($canViewTicketTypes): ?>
                    <?php if($ticketTypesIndexUrl): ?>
                    <a href="<?php echo e($ticketTypesIndexUrl); ?>" class="<?php echo e(request()->routeIs('ticket-types.*') || request()->is('ticket-types*') ? 'active' : ''); ?>">
                        <span class="label">Ticket Types</span>
                    </a>
                    <?php else: ?>
                    <a href="#" class="disabled" aria-disabled="true" tabindex="-1" onclick="return false;">
                        <span class="label">Ticket Types</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($canViewTickets): ?>
                    <?php if($ticketsIndexUrl): ?>
                    <a href="<?php echo e($ticketsIndexUrl); ?>" class="<?php echo e((request()->routeIs('tickets.*') && !request()->routeIs('tickets.pending')) || (request()->is('tickets*') && !request()->is('tickets/pending')) ? 'active' : ''); ?>">
                        <span class="label">All Tickets</span>
                    </a>
                    <?php else: ?>
                    <a href="#" class="disabled" aria-disabled="true" tabindex="-1" onclick="return false;">
                        <span class="label">All Tickets</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($canViewPendingTickets && \Illuminate\Support\Facades\Route::has('tickets.pending')): ?>
                    <a href="<?php echo e(route('tickets.pending')); ?>" class="<?php echo e(request()->routeIs('tickets.pending') ? 'active' : ''); ?>">
                        <span class="label">Pending Tickets</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer muted">
        <span class="label">© <?php echo e(date('Y')); ?> IOMS</span>
    </div>
</aside>
<?php /**PATH E:\xampp\htdocs\IOMS\resources\views/partials/sidebar.blade.php ENDPATH**/ ?>