<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            ['name' => 'Assign Users to Branches', 'slug' => 'branches.assign-users', 'group' => 'branches', 'guard_name' => 'web'],

            // Permissions (Role Permission Management)
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Manage Role Permissions', 'slug' => 'permissions.manage', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Create New Permission', 'slug' => 'permissions.create', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Edit Permission Details', 'slug' => 'permissions.edit', 'group' => 'permissions', 'guard_name' => 'web'],
            ['name' => 'Delete Permissions', 'slug' => 'permissions.delete', 'group' => 'permissions', 'guard_name' => 'web'],

            ['name' => 'View Contract Types', 'slug' => 'contract-types.view', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Create New Contract Type', 'slug' => 'contract-types.create', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Edit Contract Type Details', 'slug' => 'contract-types.edit', 'group' => 'contract-types', 'guard_name' => 'web'],
            ['name' => 'Delete Contract Types', 'slug' => 'contract-types.delete', 'group' => 'contract-types', 'guard_name' => 'web'],

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
            DB::table('permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        // Ensure a single Developer role exists as GLOBAL with priority=1
        $roleData = ['name' => 'Developer'];
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'slug')) {
            $roleData['slug'] = 'developer';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'description')) {
            $roleData['description'] = 'Developer role with full permissions';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'is_active')) {
            $roleData['is_active'] = true;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'guard_name')) {
            $roleData['guard_name'] = 'web';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'is_global')) {
            $roleData['is_global'] = true;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'priority')) {
            $roleData['priority'] = 1;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('roles', 'branch_id')) {
            $roleData['branch_id'] = null;
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'Developer'],
            array_merge($roleData, ['updated_at' => now(), 'created_at' => now()])
        );

        $developerRoleId = DB::table('roles')->where('name', 'Developer')->value('id');

        // Assign all permissions to Developer in pivot table
        $permissionIds = DB::table('permissions')->pluck('id')->all();

        // Clear other role_has_permissions and model_has_roles entries
        DB::table('role_has_permissions')->where('role_id', '!=', $developerRoleId)->delete();
        DB::table('model_has_roles')->where('role_id', '!=', $developerRoleId)->delete();
        // Delete other roles (schema may not have the extra columns, so use DB)
        DB::table('roles')->where('id', '!=', $developerRoleId)->delete();

        // Insert role_has_permissions entries for Developer
        foreach ($permissionIds as $pid) {
            DB::table('role_has_permissions')->updateOrInsert([
                'role_id' => $developerRoleId,
                'permission_id' => $pid,
            ], []);
        }

        // Assign Developer role to user id 1 if that user exists (use model relations if available)
        $user1 = User::find(1);
        if ($user1) {
            // try to use available relation but fall back to direct DB insert
            if (method_exists($user1, 'customRoles')) {
                $user1->customRoles()->syncWithoutDetaching([$developerRoleId]);
            } elseif (method_exists($user1, 'roles')) {
                $user1->roles()->syncWithoutDetaching([$developerRoleId]);
            } else {
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $developerRoleId,
                    'model_id' => $user1->id,
                ], ['model_type' => get_class($user1), 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $this->command->info('✓ Permissions seeded and Developer role created/ensured as primary role.');
        $this->command->info('✓ All other roles removed; Developer assigned to User ID 1 if present.');
    }
}
