<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Jobs\SendNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use Illuminate\Console\Command;

class ReconcileNotificationDeliveries extends Command
{
    protected $signature = 'notifications:reconcile
                            {--minutes=5 : Minimum age of a stuck pending delivery}';

    protected $description = 'Requeue pending notification deliveries that appear to be stuck';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $threshold = now()->subMinutes($minutes);
        $dispatched = 0;

        NotificationDelivery::query()
            ->where('status', DeliveryStatus::Pending->value)
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where('updated_at', '<=', $threshold)
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->chunkById(100, function ($deliveries) use (&$dispatched): void {
                foreach ($deliveries as $delivery) {
                    SendNotificationDeliveryJob::dispatch(
                        deliveryId: $delivery->id,
                        channel: $delivery->channel,
                        maxAttempts: $delivery->max_attempts,
                    );

                    $delivery->touch();
                    $dispatched++;
                }
            });

        $this->info("Requeued {$dispatched} pending notification deliveries.");

        return self::SUCCESS;
    }
}
