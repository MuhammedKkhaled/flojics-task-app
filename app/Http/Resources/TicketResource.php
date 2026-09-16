<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ticket */
class TicketResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
            'status' => $this->status->value,
            'can_escalate' => $this->status->canEscalate(),
            'escalated_at' => $this->escalated_at?->toIso8601String(),
            'escalation_level' => $this->escalation_level,
            'customer' => $this->whenLoaded('customer', fn () => $this->customer?->user?->name),
            'agent' => $this->whenLoaded('agent', fn () => $this->agent?->user?->name),
            'escalation' => TicketEscalationResource::make(
                $this->whenLoaded('latestEscalation'),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
