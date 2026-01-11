<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Branch;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.view')) {
            return false;
        }

        // Non-superadmin users can only view their own branch
        return $branch->id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.edit')) {
            return false;
        }

        // Non-superadmin users can only edit their own branch
        return $branch->id === $user->branch_id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.delete')) {
            return false;
        }

        // Non-superadmin users can only delete their own branch
        return $branch->id === $user->branch_id;
    }

    public function restore(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.restore')) {
            return false;
        }

        // Non-superadmin users can only restore their own branch
        return $branch->id === $user->branch_id;
    }

    public function export(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('branches.export')) {
            return false;
        }

        // Non-superadmin users can only export their own branch
        return $branch->id === $user->branch_id;
    }
}
