<?php

namespace App\Policies;

use App\Models\TicketType;
use App\Models\User;

class TicketTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('ticket-types.view');
    }

    public function view(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.view')) {
            return false;
        }

        // Branch users can only view ticket types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) return true;
        return $user->hasPermission('ticket-types.create');
    }

    public function update(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.edit')) {
            return false;
        }

        // Branch users can only edit ticket types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.delete')) {
            return false;
        }

        // Branch users can only delete ticket types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketType->branch_id === $activeBranchId;
        }

        return true;
    }

    public function restore(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.restore')) {
            return false;
        }

        // Branch users can only restore ticket types in their active branch
        if (!$user->global_role_id) {
            $activeBranchId = session('active_branch_id');
            if (!$activeBranchId) {
                return false;
            }
            
            return $ticketType->branch_id === $activeBranchId;
        }

        return true;
    }
}
