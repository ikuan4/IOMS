<?php

namespace App\Policies;

use App\Models\TicketModule;
use App\Models\User;

class TicketModulePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('ticket-modules.view');
    }

    public function view(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.view')) {
            return false;
        }

        // Branch users can only view ticket modules in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketModule->branch_id === $activeBranchId;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('ticket-modules.create');
    }

    public function update(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.edit')) {
            return false;
        }

        // Branch users can only edit ticket modules in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketModule->branch_id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.delete')) {
            return false;
        }

        // Branch users can only delete ticket modules in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketModule->branch_id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.restore')) {
            return false;
        }

        // Branch users can only restore ticket modules in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketModule->branch_id === $activeBranchId;
        }

        return true;
    }
}
