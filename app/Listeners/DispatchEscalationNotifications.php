<?php

namespace App\Listeners;

use App\Enums\DeliveryStatus;
use App\Events\TicketEscalated;
use App\Jobs\SendNotificationDeliveryJob;
use App\Models\NotificationDelivery;

class DispatchEscalationNotifications
{
    public function handle(TicketEscalated $event): void
    {
        $event->escalation->deliveries()
            ->where('status', DeliveryStatus::Pending->value)
            ->each(function (NotificationDelivery $delivery): void {
                SendNotificationDeliveryJob::dispatch(
                    deliveryId: $delivery->id,
                    channel: $delivery->channel,
                    maxAttempts: $delivery->max_attempts,
                );
            });
    }
}
