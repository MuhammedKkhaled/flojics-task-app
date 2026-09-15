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
        ],
        'slack' => [
            'label' => 'Slack',
            'driver' => SlackChannel::class,
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
    ],

    'timeout' => (int) env('NOTIFICATIONS_TIMEOUT', 15),
    'simulate_failure' => env('NOTIFICATIONS_SIMULATE_FAILURE'),
];
