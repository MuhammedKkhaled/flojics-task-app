<?php

namespace App\Notifications\Channels;

use App\Mail\EscalationMail;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Exceptions\PermanentNotificationException;
use App\Notifications\Exceptions\TransientNotificationException;
use App\Notifications\Messages\EscalationMessage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class EmailChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'email';
    }

    public function recipientsFor(Ticket $ticket): array
    {
        /** @var list<string> $configured */
        $configured = config('notifications.channels.email.recipients', []);

        if ($configured !== []) {
            return array_values(array_unique($configured));
        }

        $recipients = User::query()
            ->where('role', 'admin')
            ->pluck('email')
            ->all();

        $assignedAgent = $ticket->agent?->user?->email;

        if ($assignedAgent !== null) {
            $recipients[] = $assignedAgent;
        }

        return array_values(array_unique($recipients));
    }

    public function send(EscalationMessage $message, string $recipient): void
    {
        if ($this->simulatesFailure()) {
            throw new TransientNotificationException(
                'Simulated email provider failure.',
                'simulated_failure',
                503,
            );
        }

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            throw new PermanentNotificationException(
                "Invalid email recipient [{$recipient}].",
                'invalid_recipient',
            );
        }

        try {
            Mail::to($recipient)->send(new EscalationMail($message));
        } catch (TransportExceptionInterface $exception) {
            throw new TransientNotificationException(
                $exception->getMessage(),
                'email_transport_error',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new PermanentNotificationException(
                $exception->getMessage(),
                'email_render_error',
                previous: $exception,
            );
        }
    }

    private function simulatesFailure(): bool
    {
        $channels = array_map(
            'trim',
            explode(',', (string) config('notifications.simulate_failure', '')),
        );

        return in_array($this->key(), $channels, true);
    }
}
