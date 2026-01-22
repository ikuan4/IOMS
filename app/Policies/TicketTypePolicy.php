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

        return $ticketType->branch_id === $user->branch_id;
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

        return $ticketType->branch_id === $user->branch_id;
    }

    public function delete(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.delete')) {
            return false;
        }

        return $ticketType->branch_id === $user->branch_id;
    }

    public function restore(User $user, TicketType $ticketType): bool
    {
        if ($user->isSuperAdmin()) return true;

        if (!$user->hasPermission('ticket-types.restore')) {
            return false;
        }

        return $ticketType->branch_id === $user->branch_id;
    }
}
