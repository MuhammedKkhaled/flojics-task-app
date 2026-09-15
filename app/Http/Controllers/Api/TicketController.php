<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function index(): JsonResponse
    {
        $tickets = Ticket::query()
            ->with(['customer.user', 'agent.user'])
            ->latest()
            ->get()
            ->map(fn (Ticket $ticket): array => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'customer' => $ticket->customer?->user?->name,
                'agent' => $ticket->agent?->user?->name,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $tickets]);
    }
}
