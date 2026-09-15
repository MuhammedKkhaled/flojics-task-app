<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $agent = Agent::query()
            ->whereRelation('user', 'email', 'agent@flojics.test')
            ->firstOrFail();

        $customers = Customer::query()->orderBy('id')->get();

        $tickets = [
            ['Cannot access analytics dashboard', 'urgent', 'open'],
            ['Invoice contains duplicate line item', 'high', 'in_progress'],
            ['Update account billing address', 'low', 'resolved'],
            ['Webhook delivery timing out', 'urgent', 'in_progress'],
            ['New team member invitation failed', 'medium', 'open'],
            ['Export completed with missing rows', 'high', 'closed'],
            ['Request for plan comparison', 'low', 'open'],
            ['Two-factor recovery assistance', 'medium', 'resolved'],
        ];

        foreach ($tickets as $index => [$subject, $priority, $status]) {
            Ticket::factory()->create([
                'customer_id' => $customers[$index % $customers->count()]->id,
                'agent_id' => $index === 6 ? null : $agent->id,
                'subject' => $subject,
                'priority' => $priority,
                'status' => $status,
            ]);
        }
    }
}
