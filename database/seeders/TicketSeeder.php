<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
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
            ['Cannot access analytics dashboard', 'urgent', TicketStatus::Open],
            ['Invoice contains duplicate line item', 'high', TicketStatus::InProgress],
            ['Update account billing address', 'low', TicketStatus::Resolved],
            ['Webhook delivery timing out', 'urgent', TicketStatus::InProgress],
            ['New team member invitation failed', 'medium', TicketStatus::Open],
            ['Export completed with missing rows', 'high', TicketStatus::Closed],
            ['Request for plan comparison', 'low', TicketStatus::Open],
            ['Two-factor recovery assistance', 'medium', TicketStatus::Resolved],
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
