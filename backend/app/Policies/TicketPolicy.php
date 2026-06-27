<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $ticket->requester_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }
}
