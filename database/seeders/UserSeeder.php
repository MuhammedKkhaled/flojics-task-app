<?php

namespace Database\Seeders;

use App\Enums\AgentTeam;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Flojics Admin',
            'email' => 'admin@flojics.test',
        ]);

        $agentUser = User::factory()->agent()->create([
            'name' => 'Mona Hassan',
            'email' => 'agent@flojics.test',
        ]);

        Agent::factory()->create([
            'user_id' => $agentUser->id,
            'team' => AgentTeam::Technical,
        ]);
    }
}
