<?php

namespace App\Domain\Tickets\Exceptions;

use DomainException;

class TicketAlreadyEscalated extends DomainException
{
    public function __construct()
    {
        parent::__construct('This ticket has already been escalated.');
    }
}
