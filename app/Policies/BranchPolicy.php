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
        return $user->hasPermission('branches.view');
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.edit');
    }

    public function delete(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.delete');
    }

    public function restore(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.restore');
    }

    public function export(User $user, Branch $branch): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('branches.export');
    }
}
