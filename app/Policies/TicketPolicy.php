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

        return $ticket->branch_id === $user->branch_id;
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

        return $ticket->branch_id === $user->branch_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.delete')) {
            return false;
        }

        return $ticket->branch_id === $user->branch_id;
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('tickets.restore')) {
            return false;
        }

        return $ticket->branch_id === $user->branch_id;
    }
}
