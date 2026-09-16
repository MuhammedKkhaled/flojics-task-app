<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'status' => $this->status->value,
            'attempts' => $this->attempts,
            'max_attempts' => $this->max_attempts,
            'next_attempt_at' => $this->next_attempt_at?->toIso8601String(),
            'last_error' => $this->last_error,
            'last_error_code' => $this->last_error_code,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'failed_at' => $this->failed_at?->toIso8601String(),
        ];
    }
}
