<?php

namespace App\Http\Controllers\Api;

use App\Actions\Tickets\EscalateTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\EscalateTicketRequest;
use App\Http\Resources\TicketEscalationResource;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TicketEscalationController extends Controller
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

        return (new TicketEscalationResource($escalation))
            ->response()
            ->setStatusCode(201);
    }
}
