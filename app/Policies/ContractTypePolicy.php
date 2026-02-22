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

        // Branch users can only view contract types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contractType->branch_id === $activeBranchId;
        }

        return true;
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

        // Branch users can only edit contract types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contractType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.delete')) {
            return false;
        }

        // Branch users can only delete contract types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contractType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, ContractType $contractType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contract-types.restore')) {
            return false;
        }

        // Branch users can only restore contract types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contractType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function export(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contract-types.export');
    }
}
