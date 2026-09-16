<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $tickets = Ticket::query()
            ->with(['customer.user', 'agent.user'])
            ->latest()
            ->get();

        return TicketResource::collection($tickets);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $ticket->load([
            'customer.user',
            'agent.user',
            'latestEscalation.actor',
            'latestEscalation.deliveries',
        ]);

        return new TicketResource($ticket);
    }
}
