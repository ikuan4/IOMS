<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.view')) {
            return false;
        }

        // Branch users can only view tickets in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticket->branch_id === $activeBranchId;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.edit')) {
            return false;
        }

        // Branch users can only edit tickets in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticket->branch_id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.delete')) {
            return false;
        }

        // Branch users can only delete tickets in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticket->branch_id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.restore')) {
            return false;
        }

        // Branch users can only restore tickets in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticket->branch_id === $activeBranchId;
        }

        return true;
    }
}
