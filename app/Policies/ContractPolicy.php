<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contract;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contracts.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.view')) {
            return false;
        }

        // Branch users can only view contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.edit')) {
            return false;
        }

        // Branch users can only edit contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.delete')) {
            return false;
        }

        // Branch users can only delete contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.restore')) {
            return false;
        }

        // Branch users can only restore contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }

    public function export(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('contracts.export');
    }

    public function viewVersions(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract);
    }

    public function createVersion(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function manageReminders(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.manage-reminders')) {
            return false;
        }

        // Branch users can only manage reminders for contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }

    public function manageRecipients(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.manage-recipients')) {
            return false;
        }

        // Branch users can only manage recipients for contracts in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $contract->branch_id === $activeBranchId;
        }

        return true;
    }
}
