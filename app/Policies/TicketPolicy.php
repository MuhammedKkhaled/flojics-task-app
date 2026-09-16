<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function escalate(User $user, Ticket $ticket): bool
    {
        return in_array($user->role, ['agent', 'admin'], true);
    }
}
