<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tickets\EscalateTicketAction;
use App\Http\Requests\EscalateTicketRequest;
use App\Http\Resources\TicketEscalationResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TicketEscalationController extends BaseApiController
{
    public function store(
        EscalateTicketRequest $request,
        Ticket $ticket,
        EscalateTicketAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $escalation = $action->execute(
            ticket: $ticket,
            actor: $actor,
            channelKeys: $request->channels(),
            reason: $request->reason(),
        );

        return $this
            ->setStatusCode(201)
            ->fromResource(new TicketEscalationResource($escalation))
            ->toResponse();
    }
}
