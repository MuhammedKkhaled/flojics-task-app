<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'agent_id' => Agent::factory(),
            'subject' => fake()->sentence(5),
            'description' => fake()->paragraphs(2, true),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'closed']),
        ];
    }

    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => ['agent_id' => null]);
    }
}
