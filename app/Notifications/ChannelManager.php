<?php

namespace App\Notifications;

use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Exceptions\UnknownChannelException;
use Illuminate\Contracts\Container\Container;
use LogicException;

class ChannelManager
{
    public function __construct(private readonly Container $container) {}

    public function resolve(string $key): NotificationChannel
    {
        $definition = $this->registry()[$key] ?? null;

        if (! is_array($definition)) {
            throw new UnknownChannelException($key);
        }

        $driver = $definition['driver'] ?? null;

        if (! is_string($driver)) {
            throw new LogicException("Notification channel [{$key}] has no valid driver.");
        }

        $channel = $this->container->make($driver);

        if (! $channel instanceof NotificationChannel) {
            throw new LogicException("Notification channel driver [{$driver}] must implement NotificationChannel.");
        }

        if ($channel->key() !== $key) {
            throw new LogicException("Notification channel [{$key}] resolves to mismatched driver [{$channel->key()}].");
        }

        return $channel;
    }

    /** @return list<string> */
    public function available(): array
    {
        return array_keys($this->registry());
    }

    /** @return list<array{key: string, label: string}> */
    public function catalog(): array
    {
        $catalog = [];

        foreach ($this->registry() as $key => $definition) {
            $catalog[] = [
                'key' => $key,
                'label' => (string) ($definition['label'] ?? ucfirst($key)),
            ];
        }

        return $catalog;
    }

    /** @return array<string, array<string, mixed>> */
    private function registry(): array
    {
        /** @var array<string, array<string, mixed>> $registry */
        $registry = config('notifications.channels', []);

        return $registry;
    }
}
