<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.view');
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermission('users.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check branch match
        if ($model->branch_id !== $user->branch_id) {
            return false;
        }

        // Check hierarchy: can only view users with lower hierarchy (higher priority number)
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $modelPriority = $model->effectiveRole()?->priority ?? 999;

        return $modelPriority > $userPriority;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.create');
    }

    public function update(User $user, User $model): bool
    {
        // Only the developer can edit the developer user
        if ($model->isSuperAdmin() && !$user->isSuperAdmin()) {
            return false;
        }

        if (!$user->hasPermission('users.edit')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check branch match
        if ($model->branch_id !== $user->branch_id) {
            return false;
        }

        // Check hierarchy: can only update users with lower hierarchy (higher priority number)
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $modelPriority = $model->effectiveRole()?->priority ?? 999;

        return $modelPriority > $userPriority;
    }

    public function delete(User $user, User $model): bool
    {
        // Prevent deleting the developer user
        if ($model->isSuperAdmin()) {
            return false;
        }

        if (!$user->hasPermission('users.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check branch match
        if ($model->branch_id !== $user->branch_id) {
            return false;
        }

        // Check hierarchy: can only delete users with lower hierarchy (higher priority number)
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $modelPriority = $model->effectiveRole()?->priority ?? 999;

        return $modelPriority > $userPriority;
    }

    public function restore(User $user, User $model): bool
    {
        if (!$user->hasPermission('users.restore')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check branch match
        if ($model->branch_id !== $user->branch_id) {
            return false;
        }

        // Check hierarchy: can only restore users with lower hierarchy (higher priority number)
        $userPriority = $user->effectiveRole()?->priority ?? 999;
        $modelPriority = $model->effectiveRole()?->priority ?? 999;

        return $modelPriority > $userPriority;
    }
}
