<?php

namespace Database\Factories;

use App\Enums\AgentTeam;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Agent> */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->agent(),
            'team' => fake()->randomElement(AgentTeam::cases()),
        ];
    }
}
