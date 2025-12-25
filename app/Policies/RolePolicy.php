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
        return $user->hasPermission('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->isSuperAdmin()) {
            return false;
        }

        return $user->hasPermission('roles.edit') &&
            ($user->isSuperAdmin() || $user->canManageRole($role));
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->isSuperAdmin()) {
            return false;
        }

        if ($role->isAdmin()) {
            return false;
        }

        if ($role->users()->count() > 0) {
            return false;
        }

        return $user->hasPermission('roles.delete') &&
            ($user->isSuperAdmin() || $user->canManageRole($role));
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
        return $user->hasPermission('roles.delete');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
