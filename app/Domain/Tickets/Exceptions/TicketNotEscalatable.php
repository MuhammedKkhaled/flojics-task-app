<?php

namespace App\Domain\Tickets\Exceptions;

use App\Enums\TicketStatus;
use DomainException;

class TicketNotEscalatable extends DomainException
{
    public function __construct(TicketStatus $status)
    {
        parent::__construct("Tickets with status [{$status->value}] cannot be escalated.");
    }
}
