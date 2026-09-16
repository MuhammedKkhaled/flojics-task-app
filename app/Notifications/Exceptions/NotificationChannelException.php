<?php

namespace App\Notifications\Exceptions;

use RuntimeException;
use Throwable;

abstract class NotificationChannelException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $notificationErrorCode,
        private readonly ?int $notificationHttpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function errorCode(): string
    {
        return $this->notificationErrorCode;
    }

    public function httpStatus(): ?int
    {
        return $this->notificationHttpStatus;
    }
}
