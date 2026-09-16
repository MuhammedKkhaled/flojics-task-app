<?php

namespace Tests\Contracts;

use App\Models\Ticket;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\Messages\EscalationMessage;
use DateTimeImmutable;
use Tests\TestCase;

abstract class ChannelContractTestCase extends TestCase
{
    abstract protected function channel(): NotificationChannel;

    abstract protected function expectedKey(): string;

    abstract protected function validRecipient(): string;

    abstract protected function fakeSuccessfulProvider(): void;

    abstract protected function assertProviderReceivedMessage(): void;

    final public function test_it_exposes_a_stable_key_and_recipient_list(): void
    {
        $channel = $this->channel();

        $this->assertSame($this->expectedKey(), $channel->key());
        $this->assertContains($this->validRecipient(), $channel->recipientsFor(new Ticket));
    }

    final public function test_it_sends_a_channel_agnostic_escalation_message(): void
    {
        $this->fakeSuccessfulProvider();

        $this->channel()->send($this->message(), $this->validRecipient());

        $this->assertProviderReceivedMessage();
    }

    private function message(): EscalationMessage
    {
        return new EscalationMessage(
            ticketId: 42,
            subject: 'Production API is unavailable',
            priority: 'urgent',
            escalatedAt: new DateTimeImmutable('2026-09-16T10:00:00+00:00'),
            reason: 'Customer operations are blocked.',
            actor: 'Mona Hassan',
            correlationUuid: '0e793564-bc4d-461a-87c9-c724ecbac6d4',
        );
    }
}
