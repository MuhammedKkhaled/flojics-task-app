<?php

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SlackChannel;

return [
    /*
    | Channel classes are container-resolved in Phase 2. Labels are API-safe
    | metadata consumed by the Vue selector.
    */
    'channels' => [
        'email' => [
            'label' => 'Email',
            'driver' => EmailChannel::class,
            'recipients' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('NOTIFICATIONS_EMAIL_RECIPIENTS', '')),
            ))),
            'rate_limit' => (int) env('NOTIFICATIONS_EMAIL_RATE_LIMIT', 60),
        ],
        'slack' => [
            'label' => 'Slack',
            'driver' => SlackChannel::class,
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'recipient' => env('SLACK_CHANNEL', '#support-escalations'),
            'rate_limit' => (int) env('NOTIFICATIONS_SLACK_RATE_LIMIT', 30),
        ],
    ],

    'default_channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOTIFICATIONS_DEFAULT_CHANNELS', 'email,slack')),
    ))),

    'retry' => [
        // Includes the initial attempt: 1 initial + 3 retries.
        'max_attempts' => (int) env('NOTIFICATIONS_MAX_ATTEMPTS', 4),
        'backoff' => [10, 60, 300],
        'jitter' => (int) env('NOTIFICATIONS_RETRY_JITTER', 5),
    ],

    'timeout' => (int) env('NOTIFICATIONS_TIMEOUT', 15),
    'simulate_failure' => env('NOTIFICATIONS_SIMULATE_FAILURE'),
];
