<?php

namespace Tests\Feature\Notifications;

use App\Notifications\Channels\SlackChannel;
use App\Notifications\Contracts\NotificationChannel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Contracts\ChannelContractTestCase;

class SlackChannelContractTest extends ChannelContractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('notifications.simulate_failure');
        config()->set('notifications.channels.slack.webhook_url', 'https://hooks.slack.test/services/example');
        config()->set('notifications.channels.slack.recipient', $this->validRecipient());
    }

    protected function channel(): NotificationChannel
    {
        return app(SlackChannel::class);
    }

    protected function expectedKey(): string
    {
        return 'slack';
    }

    protected function validRecipient(): string
    {
        return '#support-escalations';
    }

    protected function fakeSuccessfulProvider(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
    }

    protected function assertProviderReceivedMessage(): void
    {
        Http::assertSent(fn (Request $request): bool => $request['channel'] === $this->validRecipient()
            && $request['text'] === 'Ticket #42 was escalated');
    }
}
