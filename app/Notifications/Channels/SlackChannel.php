<?php

namespace App\Notifications\Channels;

use App\Models\Ticket;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Exceptions\PermanentNotificationException;
use App\Notifications\Exceptions\TransientNotificationException;
use App\Notifications\Messages\EscalationMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SlackChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'slack';
    }

    public function recipientsFor(Ticket $ticket): array
    {
        return [(string) config('notifications.channels.slack.recipient', '#support-escalations')];
    }

    public function send(EscalationMessage $message, string $recipient): void
    {
        if ($this->simulatesFailure()) {
            throw new TransientNotificationException(
                'Simulated Slack provider failure.',
                'simulated_failure',
                503,
            );
        }

        $webhookUrl = (string) config('notifications.channels.slack.webhook_url', '');

        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) === false) {
            throw new PermanentNotificationException(
                'The Slack webhook URL is not configured.',
                'slack_not_configured',
            );
        }

        try {
            $response = Http::timeout((int) config('notifications.timeout', 15))
                ->acceptJson()
                ->post($webhookUrl, $this->payload($message, $recipient));
        } catch (ConnectionException $exception) {
            throw new TransientNotificationException(
                $exception->getMessage(),
                'slack_connection_error',
                previous: $exception,
            );
        }

        if ($response->status() === 429) {
            throw new TransientNotificationException(
                'Slack rate limit reached.',
                'slack_rate_limited',
                429,
            );
        }

        if ($response->serverError()) {
            throw new TransientNotificationException(
                'Slack returned a server error.',
                'slack_server_error',
                $response->status(),
            );
        }

        if (! $response->successful()) {
            throw new PermanentNotificationException(
                'Slack rejected the webhook payload.',
                'slack_request_rejected',
                $response->status(),
            );
        }
    }

    /** @return array<string, mixed> */
    public function payload(EscalationMessage $message, string $recipient): array
    {
        $reason = $message->reason === null ? '' : "\n*Reason:* {$message->reason}";

        return [
            'channel' => $recipient,
            'text' => "Ticket #{$message->ticketId} was escalated",
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "*Escalated ticket #{$message->ticketId}*\n{$message->subject}",
                    ],
                ],
                [
                    'type' => 'section',
                    'fields' => [
                        ['type' => 'mrkdwn', 'text' => '*Priority:* '.strtoupper($message->priority)],
                        ['type' => 'mrkdwn', 'text' => "*Actor:* {$message->actor}"],
                        ['type' => 'mrkdwn', 'text' => '*Escalated:* '.$message->escalatedAt->format(DATE_ATOM)],
                        ['type' => 'mrkdwn', 'text' => "*Correlation:* `{$message->correlationUuid}`{$reason}"],
                    ],
                ],
            ],
        ];
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
