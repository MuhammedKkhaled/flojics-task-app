<?php

namespace App\Notifications\Exceptions;

use InvalidArgumentException;

class UnknownChannelException extends InvalidArgumentException
{
    public function __construct(string $key)
    {
        parent::__construct("Notification channel [{$key}] is not configured.");
    }
}
