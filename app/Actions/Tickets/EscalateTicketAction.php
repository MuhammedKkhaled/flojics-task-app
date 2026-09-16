<?php

namespace App\Actions\Tickets;

use App\Domain\Tickets\Exceptions\TicketAlreadyEscalated;
use App\Domain\Tickets\Exceptions\TicketNotEscalatable;
use App\Enums\DeliveryStatus;
use App\Enums\EscalationStatus;
use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;

class EscalateTicketAction
{
    public function __construct(private readonly ChannelManager $channels) {}

    /** @param list<string> $channelKeys */
    public function execute(
        Ticket $ticket,
        User $actor,
        array $channelKeys,
        ?string $reason = null,
    ): TicketEscalation {
        return DB::transaction(function () use ($ticket, $actor, $channelKeys, $reason): TicketEscalation {
            $lockedTicket = Ticket::query()
                ->lockForUpdate()
                ->findOrFail($ticket->getKey());

            if ($lockedTicket->status === TicketStatus::Escalated
                || $lockedTicket->escalations()->exists()) {
                throw new TicketAlreadyEscalated;
            }

            if (! $lockedTicket->status->canEscalate()) {
                throw new TicketNotEscalatable($lockedTicket->status);
            }

            $escalatedAt = now();
            $level = 1;

            $lockedTicket->update([
                'status' => TicketStatus::Escalated,
                'escalated_at' => $escalatedAt,
                'escalation_level' => $level,
            ]);

            $escalation = $lockedTicket->escalations()->create([
                'actor_id' => $actor->getKey(),
                'level' => $level,
                'status' => EscalationStatus::Pending,
                'reason' => $reason,
                'escalated_at' => $escalatedAt,
            ]);

            $lockedTicket->loadMissing(['agent.user']);

            foreach ($channelKeys as $channelKey) {
                $channel = $this->channels->resolve($channelKey);
                $recipients = array_values(array_unique(array_filter(array_map(
                    'trim',
                    $channel->recipientsFor($lockedTicket),
                ))));

                foreach ($recipients as $recipient) {
                    $escalation->deliveries()->create([
                        'channel' => $channel->key(),
                        'recipient' => $recipient,
                        'status' => DeliveryStatus::Pending,
                        'attempts' => 0,
                        'max_attempts' => (int) config('notifications.retry.max_attempts', 4),
                    ]);
                }
            }

            TicketEscalated::dispatch($escalation);

            return $escalation->load(['actor', 'deliveries']);
        });
    }
}
