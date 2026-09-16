<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\TicketEscalation;
use App\Notifications\ChannelManager;
use App\Notifications\Exceptions\PermanentNotificationException;
use App\Notifications\Exceptions\TransientNotificationException;
use App\Notifications\Messages\EscalationMessage;
use App\Services\Notifications\DeliveryRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class SendNotificationDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public bool $failOnTimeout = true;

    /** @var list<int> */
    private array $retryDelays;

    public function __construct(
        public int $deliveryId,
        public string $channel,
        int $maxAttempts = 4,
    ) {
        $this->tries = $maxAttempts;
        $this->timeout = (int) config('notifications.timeout', 15);

        $jitter = max(0, (int) config('notifications.retry.jitter', 5));
        $backoff = config('notifications.retry.backoff', [10, 60, 300]);

        $this->retryDelays = array_map(
            fn ($delay): int => (int) $delay + random_int(0, $jitter),
            is_array($backoff) ? array_values($backoff) : [10, 60, 300],
        );
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("notification-delivery:{$this->deliveryId}"))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 5),
            new RateLimited('notification-channel'),
        ];
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return $this->retryDelays;
    }

    public function handle(ChannelManager $channels, DeliveryRecorder $recorder): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if ($delivery === null || $delivery->status->isTerminal()) {
            return;
        }

        if ($delivery->attempts >= $delivery->max_attempts) {
            $this->failDelivery(
                new RuntimeException('Notification delivery has exhausted its attempts.'),
                $recorder,
            );

            return;
        }

        $startedAt = hrtime(true);

        try {
            $channels->resolve($delivery->channel)->send(
                $this->messageFor($delivery),
                $delivery->recipient,
            );

            $recorder->recordSent($delivery->id, $this->durationSince($startedAt));
        } catch (TransientNotificationException $exception) {
            $attemptNumber = $delivery->attempts + 1;
            $exhausted = $attemptNumber >= $delivery->max_attempts;
            $delay = $exhausted ? null : $this->delayAfter($attemptNumber);

            $recorder->recordFailure(
                deliveryId: $delivery->id,
                exception: $exception,
                durationMs: $this->durationSince($startedAt),
                outcome: 'transient_failure',
                nextAttemptAt: $delay === null ? null : now()->addSeconds($delay),
            );

            if ($exhausted) {
                $this->failDelivery($exception, $recorder);

                return;
            }

            $this->release($delay);
        } catch (PermanentNotificationException $exception) {
            $recorder->recordFailure(
                deliveryId: $delivery->id,
                exception: $exception,
                durationMs: $this->durationSince($startedAt),
                outcome: 'permanent_failure',
                nextAttemptAt: null,
            );

            $this->failDelivery($exception, $recorder);
        } catch (Throwable $exception) {
            $recorder->recordFailure(
                deliveryId: $delivery->id,
                exception: $exception,
                durationMs: $this->durationSince($startedAt),
                outcome: 'unexpected_failure',
                nextAttemptAt: null,
            );

            $this->failDelivery($exception, $recorder);
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(DeliveryRecorder::class)->markFailed($this->deliveryId, $exception);
    }

    private function messageFor(NotificationDelivery $delivery): EscalationMessage
    {
        $escalation = $delivery->notifiable;

        if (! $escalation instanceof TicketEscalation) {
            throw new PermanentNotificationException(
                'The delivery is not attached to a ticket escalation.',
                'invalid_notifiable',
            );
        }

        $escalation->loadMissing(['ticket', 'actor']);

        return new EscalationMessage(
            ticketId: $escalation->ticket->id,
            subject: $escalation->ticket->subject,
            priority: $escalation->ticket->priority,
            escalatedAt: $escalation->escalated_at,
            reason: $escalation->reason,
            actor: $escalation->actor->name,
            correlationUuid: $delivery->uuid,
        );
    }

    private function delayAfter(int $attemptNumber): int
    {
        if ($this->retryDelays === []) {
            return 300;
        }

        $index = max(0, $attemptNumber - 1);

        return $this->retryDelays[$index]
            ?? $this->retryDelays[array_key_last($this->retryDelays)];
    }

    private function durationSince(int $startedAt): int
    {
        return max(0, (int) round((hrtime(true) - $startedAt) / 1_000_000));
    }

    private function failDelivery(Throwable $exception, DeliveryRecorder $recorder): void
    {
        $recorder->markFailed($this->deliveryId, $exception);
        $this->fail($exception);
    }
}
