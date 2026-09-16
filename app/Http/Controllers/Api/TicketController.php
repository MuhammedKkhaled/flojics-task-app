<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::query()
            ->with(['customer.user', 'agent.user'])
            ->latest()
            ->get();

        return $this->fromResource(TicketResource::collection($tickets))->toResponse();
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load([
            'customer.user',
            'agent.user',
            'latestEscalation.actor',
            'latestEscalation.deliveries',
        ]);

        return $this->fromResource(new TicketResource($ticket))->toResponse();
    }
}
