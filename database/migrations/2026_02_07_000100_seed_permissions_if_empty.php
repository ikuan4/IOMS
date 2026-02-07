<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Seed baseline permissions in production exactly once.
     *
     * Rationale:
     * - Render deployment has migrated Spatie tables, but `permissions` is blank.
     * - The Roles -> Manage Permissions UI depends on populated permissions with a non-null `group`.
     * - This migration is additive and safe: it only runs when `permissions` is empty.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        try {
            if (DB::table('permissions')->count() > 0) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard', 'guard_name' => 'web'],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users', 'guard_name' => 'web'],
            ['name' => 'Create New User', 'slug' => 'users.create', 'group' => 'users', 'guard_name' => 'web'],
            ['name' => 'Edit User Details', 'slug' => 'users.edit', 'group' => 'users', 'guard_name' => 'web'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users', 'guard_name' => 'web'],
            ['name' => 'Restore Users', 'slug' => 'users.restore', 'group' => 'users', 'guard_name' => 'web'],
            ['name' => 'Assign Roles to Users', 'slug' => 'users.assign-roles', 'group' => 'users', 'guard_name' => 'web'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles', 'guard_name' => 'web'],
            ['name' => 'Create New Role', 'slug' => 'roles.create', 'group' => 'roles', 'guard_name' => 'web'],
            ['name' => 'Edit Role Details', 'slug' => 'roles.edit', 'group' => 'roles', 'guard_name' => 'web'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'roles', 'guard_name' => 'web'],
            ['name' => 'Restore Roles', 'slug' => 'roles.restore', 'group' => 'roles', 'guard_name' => 'web'],
            ['name' => 'Manage Role Hierarchy', 'slug' => 'roles.manage-priority', 'group' => 'roles', 'guard_name' => 'web'],

            // Branches
            ['name' => 'View Branches', 'slug' => 'branches.view', 'group' => 'branches', 'guard_name' => 'web'],
            ['name' => 'Create Branch', 'slug' => 'branches.create', 'group' => 'branches', 'guard_name' => 'web'],
            ['name' => 'Edit Branch', 'slug' => 'branches.edit', 'group' => 'branches', 'guard_name' => 'web'],
            ['name' => 'Delete Branch', 'slug' => 'branches.delete', 'group' => 'branches', 'guard_name' => 'web'],
            ['name' => 'Restore Branch', 'slug' => 'branches.restore', 'group' => 'branches', 'guard_name' => 'web'],
            ['name' => 'Export Branch Data', 'slug' => 'branches.export', 'group' => 'branches', 'guard_name' => 'web'],

            // Permissions (role permission management)
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Manage Role Permissions', 'slug' => 'permissions.manage', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Create New Permission', 'slug' => 'permissions.create', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Edit Permission Details', 'slug' => 'permissions.edit', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'group' => 'permissions', 'guard_name' => 'web'],

            // Contract Types
            ['name' => 'View Contract Types', 'slug' => 'contract-types.view', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Create New Contract Type', 'slug' => 'contract-types.create', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Edit Contract Type Details', 'slug' => 'contract-types.edit', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Delete Contract Types', 'slug' => 'contract-types.delete', 'group' => 'contract-types', 'guard_name' => 'web'],

            // Contracts
            ['name' => 'View Contracts', 'slug' => 'contracts.view', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Create New Contract', 'slug' => 'contracts.create', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Edit Contract Details', 'slug' => 'contracts.edit', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Delete Contracts', 'slug' => 'contracts.delete', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Restore Deleted Contracts', 'slug' => 'contracts.restore', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Manage Contract Reminders', 'slug' => 'contracts.manage-reminders', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'View Contract Versions', 'slug' => 'contracts.versions.view', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Create Contract Versions', 'slug' => 'contracts.versions.create', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Edit Contract Versions', 'slug' => 'contracts.versions.edit', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Delete Contract Versions', 'slug' => 'contracts.versions.delete', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Restore Contract Versions', 'slug' => 'contracts.versions.restore', 'group' => 'contracts', 'guard_name' => 'web'],
            ['name' => 'Export and Download Contracts', 'slug' => 'contracts.export', 'group' => 'contracts', 'guard_name' => 'web'],

            // Notifications
            ['name' => 'View Notification Recipients', 'slug' => 'notification-recipients.view', 'group' => 'notifications', 'guard_name' => 'web'],
            ['name' => 'Create New Recipient', 'slug' => 'notification-recipients.create', 'group' => 'notifications', 'guard_name' => 'web'],
            ['name' => 'Edit Recipient Details', 'slug' => 'notification-recipients.edit', 'group' => 'notifications', 'guard_name' => 'web'],
            ['name' => 'Delete Recipients', 'slug' => 'notification-recipients.delete', 'group' => 'notifications', 'guard_name' => 'web'],
            ['name' => 'Manage Notifications', 'slug' => 'notifications.manage', 'group' => 'notifications', 'guard_name' => 'web'],

            // Tickets
            ['name' => 'View Tickets', 'slug' => 'tickets.view', 'group' => 'tickets', 'guard_name' => 'web'],
            ['name' => 'View Pending Tickets', 'slug' => 'tickets.pending.view', 'group' => 'tickets', 'guard_name' => 'web'],
            ['name' => 'Create Tickets', 'slug' => 'tickets.create', 'group' => 'tickets', 'guard_name' => 'web'],
            ['name' => 'Edit Tickets', 'slug' => 'tickets.edit', 'group' => 'tickets', 'guard_name' => 'web'],
            ['name' => 'Delete Tickets', 'slug' => 'tickets.delete', 'group' => 'tickets', 'guard_name' => 'web'],
            ['name' => 'Restore Tickets', 'slug' => 'tickets.restore', 'group' => 'tickets', 'guard_name' => 'web'],

            // Ticket Types
            ['name' => 'View Ticket Types', 'slug' => 'ticket-types.view', 'group' => 'ticket-types', 'guard_name' => 'web'],
            ['name' => 'Create Ticket Types', 'slug' => 'ticket-types.create', 'group' => 'ticket-types', 'guard_name' => 'web'],
            ['name' => 'Edit Ticket Types', 'slug' => 'ticket-types.edit', 'group' => 'ticket-types', 'guard_name' => 'web'],
            ['name' => 'Delete Ticket Types', 'slug' => 'ticket-types.delete', 'group' => 'ticket-types', 'guard_name' => 'web'],
            ['name' => 'Restore Ticket Types', 'slug' => 'ticket-types.restore', 'group' => 'ticket-types', 'guard_name' => 'web'],

            // Ticket Modules
            ['name' => 'View Ticket Modules', 'slug' => 'ticket-modules.view', 'group' => 'ticket-modules', 'guard_name' => 'web'],
            ['name' => 'Create Ticket Modules', 'slug' => 'ticket-modules.create', 'group' => 'ticket-modules', 'guard_name' => 'web'],
            ['name' => 'Edit Ticket Modules', 'slug' => 'ticket-modules.edit', 'group' => 'ticket-modules', 'guard_name' => 'web'],
            ['name' => 'Delete Ticket Modules', 'slug' => 'ticket-modules.delete', 'group' => 'ticket-modules', 'guard_name' => 'web'],
            ['name' => 'Restore Ticket Modules', 'slug' => 'ticket-modules.restore', 'group' => 'ticket-modules', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            $slug = (string) ($permission['slug'] ?? '');
            if ($slug === '') {
                continue;
            }

            $now = now();
            $data = array_merge($permission, [
                'updated_at' => $now,
                'created_at' => $now,
            ]);

            // If a seeder already inserted a row (rare in prod), keep its name if non-empty.
            try {
                $existing = DB::table('permissions')->where('slug', $slug)->first();
                if ($existing && !empty($existing->name)) {
                    $data['name'] = (string) $existing->name;
                }
            } catch (Throwable) {
                // ignore
            }

            DB::table('permissions')->updateOrInsert(['slug' => $slug], $data);
        }

        // Also seed any canonical permissions configured, in case config grows over time.
        $canonical = config('permissions.canonical', []);
        foreach ($canonical as $group => $slugs) {
            if (!is_array($slugs)) {
                continue;
            }
            foreach ($slugs as $slug) {
                $slug = (string) $slug;
                if ($slug === '') {
                    continue;
                }
                $now = now();
                DB::table('permissions')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => Str::title(str_replace(['.', '-'], [' ', ' '], $slug)),
                        'slug' => $slug,
                        'group' => (string) $group,
                        'guard_name' => 'web',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: do not delete production permission data.
    }
};
