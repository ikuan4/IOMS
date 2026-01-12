<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('roles.view') || $user->hasPermission('permissions.view');
    }

    public function view(User $user, Role $role): bool
    {
        if (!$user->hasPermission('roles.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Priority hierarchy: user can only view roles with priority >= their own
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $rolePriority = $role->priority ?? 999;

        // Allow viewing roles at same level or lower privilege (higher number)
        return $rolePriority >= $userPriority;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        // Protect Developer role (ID 1) - only Developer role users can edit it
        if ($role->id === 1 && !$user->isSuperAdmin()) {
            return false;
        }

        if ($role->isSuperAdmin()) {
            return false;
        }

        if (!$user->hasPermission('roles.edit')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Priority hierarchy: user can only edit roles with priority > their own (lower privilege)
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $rolePriority = $role->priority ?? 999;

        if ($rolePriority <= $userPriority) {
            return false;
        }

        return $user->canManageRole($role);
    }

    public function delete(User $user, Role $role): bool
    {
        // Protect Developer role (ID 1) - cannot be deleted
        if ($role->id === 1) {
            return false;
        }

        if ($role->isSuperAdmin()) {
            return false;
        }

        if ($role->isAdmin()) {
            return false;
        }

        // Super admins can attempt to delete any non-protected role
        // (actual deletion will be blocked by controller if users are assigned)
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (!$user->hasPermission('roles.delete')) {
            return false;
        }

        // Priority hierarchy: user can only delete roles with priority > their own
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $rolePriority = $role->priority ?? 999;

        if ($rolePriority <= $userPriority) {
            return false;
        }

        return $user->canManageRole($role);
    }

    public function managePermissions(User $user, Role $role): bool
    {
        if ($role->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('permissions.view') &&
            ($user->isSuperAdmin() || $user->canManageRole($role));
    }

    public function updatePermissions(User $user, Role $role): bool
    {
        if ($role->isSuperAdmin()) {
            return false;
        }

        if ($user->role_id === $role->id) {
            return false;
        }

        return $user->hasPermission('permissions.manage') &&
            ($user->isSuperAdmin() || $user->canManageRole($role));
    }

    public function manageHierarchy(User $user, Role $role): bool
    {
        if ($role->isProtected()) {
            return false;
        }

        return $user->hasPermission('roles.manage-priority') &&
            ($user->isSuperAdmin() || $user->canManageRole($role));
    }

    public function restore(User $user, Role $role): bool
    {
        if (!$user->hasPermission('roles.restore')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check branch match for non-developer users
        if ($role->branch_id !== $user->branch_id) {
            return false;
        }

        // Priority hierarchy: user can only restore roles with priority > their own
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $rolePriority = $role->priority ?? 999;

        if ($rolePriority <= $userPriority) {
            return false;
        }

        return true;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
