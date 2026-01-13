<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ContractType;

class ContractTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contract-types.view');
    }

    public function view(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.view')) {
            return false;
        }

        // Non-superadmin users can only view contract types from their own branch
        return $contractType->branch_id === $user->branch_id;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contract-types.create');
    }

    public function update(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.edit')) {
            return false;
        }

        // Non-superadmin users can only edit contract types from their own branch
        return $contractType->branch_id === $user->branch_id;
    }

    public function delete(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.delete')) {
            return false;
        }

        // Non-superadmin users can only delete contract types from their own branch
        return $contractType->branch_id === $user->branch_id;
    }

    public function restore(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.restore')) {
            return false;
        }

        // Non-superadmin users can only restore contract types from their own branch
        return $contractType->branch_id === $user->branch_id;
    }

    public function export(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contract-types.export');
    }
}
