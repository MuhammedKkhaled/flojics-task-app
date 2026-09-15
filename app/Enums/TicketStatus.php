<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Escalated = 'escalated';

    public function canEscalate(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }
}
