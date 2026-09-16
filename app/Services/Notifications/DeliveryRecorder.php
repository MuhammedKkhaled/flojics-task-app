<?php

namespace App\Services\Notifications;

use App\Enums\DeliveryStatus;
use App\Enums\EscalationStatus;
use App\Events\NotificationDeliveryFailed;
use App\Models\NotificationDelivery;
use App\Models\TicketEscalation;
use App\Notifications\Exceptions\NotificationChannelException;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeliveryRecorder
{
    public function recordSent(int $deliveryId, int $durationMs): NotificationDelivery
    {
        return DB::transaction(function () use ($deliveryId, $durationMs): NotificationDelivery {
            $delivery = $this->lockedDelivery($deliveryId);
            $attemptNumber = $delivery->attempts + 1;

            $delivery->attempts()->create([
                'attempt_number' => $attemptNumber,
                'outcome' => 'sent',
                'duration_ms' => $durationMs,
            ]);

            $delivery->update([
                'status' => DeliveryStatus::Sent,
                'attempts' => $attemptNumber,
                'next_attempt_at' => null,
                'last_error' => null,
                'last_error_code' => null,
                'sent_at' => now(),
                'failed_at' => null,
            ]);

            $this->rollUpEscalation($delivery);

            return $delivery->refresh();
        });
    }

    public function recordFailure(
        int $deliveryId,
        Throwable $exception,
        int $durationMs,
        string $outcome,
        ?DateTimeInterface $nextAttemptAt,
    ): NotificationDelivery {
        return DB::transaction(function () use (
            $deliveryId,
            $exception,
            $durationMs,
            $outcome,
            $nextAttemptAt,
        ): NotificationDelivery {
            $delivery = $this->lockedDelivery($deliveryId);
            $attemptNumber = $delivery->attempts + 1;

            $delivery->attempts()->create([
                'attempt_number' => $attemptNumber,
                'outcome' => $outcome,
                'error_class' => $exception::class,
                'error_code' => $this->errorCode($exception),
                'http_status' => $this->httpStatus($exception),
                'duration_ms' => $durationMs,
            ]);

            $delivery->update([
                'attempts' => $attemptNumber,
                'next_attempt_at' => $nextAttemptAt,
                'last_error' => $exception->getMessage(),
                'last_error_code' => $this->errorCode($exception),
            ]);

            return $delivery->refresh();
        });
    }

    public function markFailed(int $deliveryId, ?Throwable $exception): NotificationDelivery
    {
        $transitioned = false;

        $delivery = DB::transaction(function () use ($deliveryId, $exception, &$transitioned): NotificationDelivery {
            $delivery = $this->lockedDelivery($deliveryId);

            if ($delivery->status->isTerminal()) {
                return $delivery;
            }

            $transitioned = true;

            $delivery->update([
                'status' => DeliveryStatus::Failed,
                'next_attempt_at' => null,
                'last_error' => $exception?->getMessage() ?: $delivery->last_error,
                'last_error_code' => $exception === null
                    ? $delivery->last_error_code
                    : $this->errorCode($exception),
                'failed_at' => now(),
            ]);

            $this->rollUpEscalation($delivery);

            return $delivery->refresh();
        });

        if ($transitioned) {
            NotificationDeliveryFailed::dispatch($delivery);
        }

        return $delivery;
    }

    private function lockedDelivery(int $deliveryId): NotificationDelivery
    {
        return NotificationDelivery::query()
            ->lockForUpdate()
            ->findOrFail($deliveryId);
    }

    private function errorCode(Throwable $exception): string
    {
        if ($exception instanceof NotificationChannelException) {
            return $exception->errorCode();
        }

        return class_basename($exception);
    }

    private function httpStatus(Throwable $exception): ?int
    {
        return $exception instanceof NotificationChannelException
            ? $exception->httpStatus()
            : null;
    }

    private function rollUpEscalation(NotificationDelivery $delivery): void
    {
        $escalation = $delivery->notifiable()->first();

        if (! $escalation instanceof TicketEscalation) {
            return;
        }

        $counts = $escalation->deliveries()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        $total = $counts->sum();
        $pending = $counts->get(DeliveryStatus::Pending->value, 0);
        $sent = $counts->get(DeliveryStatus::Sent->value, 0);
        $failed = $counts->get(DeliveryStatus::Failed->value, 0);

        $status = match (true) {
            $total === 0 || $pending > 0 => EscalationStatus::Pending,
            $sent === $total => EscalationStatus::Completed,
            $failed === $total => EscalationStatus::Failed,
            default => EscalationStatus::PartiallyFailed,
        };

        if ($escalation->status !== $status) {
            $escalation->update(['status' => $status]);
        }
    }
}
