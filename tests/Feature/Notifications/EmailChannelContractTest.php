<?php

namespace Tests\Feature\Notifications;

use App\Mail\EscalationMail;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Mail;
use Tests\Contracts\ChannelContractTestCase;

class EmailChannelContractTest extends ChannelContractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('notifications.simulate_failure');
        config()->set('notifications.channels.email.recipients', [$this->validRecipient()]);
    }

    protected function channel(): NotificationChannel
    {
        return app(EmailChannel::class);
    }

    protected function expectedKey(): string
    {
        return 'email';
    }

    protected function validRecipient(): string
    {
        return 'alerts@example.test';
    }

    protected function fakeSuccessfulProvider(): void
    {
        Mail::fake();
    }

    protected function assertProviderReceivedMessage(): void
    {
        Mail::assertSent(
            EscalationMail::class,
            fn (EscalationMail $mail): bool => $mail->hasTo($this->validRecipient()),
        );
    }
}
