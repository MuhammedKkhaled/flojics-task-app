<?php

namespace App\Notifications\Contracts;

use App\Models\Ticket;
use App\Notifications\Messages\EscalationMessage;

interface NotificationChannel
{
    public function key(): string;

    /** @return list<string> */
    public function recipientsFor(Ticket $ticket): array;

    public function send(EscalationMessage $message, string $recipient): void;
}
