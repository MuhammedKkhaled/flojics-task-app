<?php

namespace App\Notifications\Messages;

use DateTimeImmutable;

final readonly class EscalationMessage
{
    public function __construct(
        public int $ticketId,
        public string $subject,
        public string $priority,
        public DateTimeImmutable $escalatedAt,
        public ?string $reason,
        public string $actor,
        public string $correlationUuid,
    ) {}
}
