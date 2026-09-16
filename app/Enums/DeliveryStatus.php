<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Sent, self::Failed], true);
    }
}
