<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketEscalationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'level' => $this->level,
            'status' => $this->status->value,
            'reason' => $this->reason,
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'actor' => $this->whenLoaded('actor', fn () => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ]),
            'deliveries' => NotificationDeliveryResource::collection(
                $this->whenLoaded('deliveries'),
            ),
        ];
    }
}
