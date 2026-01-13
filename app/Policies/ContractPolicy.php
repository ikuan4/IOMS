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

        // Non-superadmin users can only view contracts from their own branch
        return $contract->branch_id === $user->branch_id;
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

        // Non-superadmin users can only edit contracts from their own branch
        return $contract->branch_id === $user->branch_id;
    }

    public function delete(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.delete')) {
            return false;
        }

        // Non-superadmin users can only delete contracts from their own branch
        return $contract->branch_id === $user->branch_id;
    }

    public function restore(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.restore')) {
            return false;
        }

        // Non-superadmin users can only restore contracts from their own branch
        return $contract->branch_id === $user->branch_id;
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

        return $contract->branch_id === $user->branch_id;
    }

    public function manageRecipients(User $user, Contract $contract): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('contracts.manage-recipients')) {
            return false;
        }

        return $contract->branch_id === $user->branch_id;
    }
}
