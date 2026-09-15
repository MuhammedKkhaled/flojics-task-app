<?php

namespace Database\Factories;

use App\Enums\EscalationStatus;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketEscalation> */
class TicketEscalationFactory extends Factory
{
    protected $model = TicketEscalation::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'actor_id' => User::factory()->agent(),
            'level' => 1,
            'status' => EscalationStatus::Pending,
            'reason' => fake()->optional()->sentence(),
            'escalated_at' => now(),
        ];
    }
}
