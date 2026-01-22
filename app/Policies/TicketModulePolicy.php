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

        return $ticketModule->branch_id === $user->branch_id;
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

        return $ticketModule->branch_id === $user->branch_id;
    }

    public function delete(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.delete')) {
            return false;
        }

        return $ticketModule->branch_id === $user->branch_id;
    }

    public function restore(User $user, TicketModule $ticketModule): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-modules.restore')) {
            return false;
        }

        return $ticketModule->branch_id === $user->branch_id;
    }
}
