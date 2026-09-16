<?php

use App\Mail\EscalationMail;
use App\Notifications\ChannelManager;
use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\SlackChannel;
use App\Notifications\Exceptions\PermanentNotificationException;
use App\Notifications\Exceptions\TransientNotificationException;
use App\Notifications\Exceptions\UnknownChannelException;
use App\Notifications\Messages\EscalationMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function escalationMessage(): EscalationMessage
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

beforeEach(function () {
    config()->set('notifications.simulate_failure');
    config()->set('notifications.channels.slack.webhook_url', 'https://hooks.slack.test/services/example');
});

it('resolves configured channels and rejects unknown keys', function () {
    $manager = app(ChannelManager::class);

    expect($manager->available())->toBe(['email', 'slack'])
        ->and($manager->resolve('email'))->toBeInstanceOf(EmailChannel::class)
        ->and($manager->resolve('slack'))->toBeInstanceOf(SlackChannel::class);

    expect(fn () => $manager->resolve('sms'))->toThrow(UnknownChannelException::class);
});

it('exposes the channel catalog through the API', function () {
    $this->getJson('/api/notification-channels')
        ->assertOk()
        ->assertExactJson([
            'data' => [
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'slack', 'label' => 'Slack'],
            ],
        ]);
});

it('sends escalation email through the mailer', function () {
    Mail::fake();

    $message = escalationMessage();

    app(EmailChannel::class)->send($message, 'lead@example.test');

    Mail::assertSent(
        EscalationMail::class,
        fn (EscalationMail $mail): bool => $mail->hasTo('lead@example.test'),
    );

    expect((new EscalationMail($message))->render())
        ->toContain('Ticket #42 was escalated')
        ->toContain('Customer operations are blocked.');
});

it('classifies an invalid email recipient as permanent', function () {
    expect(fn () => app(EmailChannel::class)->send(escalationMessage(), 'not-an-email'))
        ->toThrow(PermanentNotificationException::class);
});

it('posts the expected Slack payload', function () {
    Http::fake(['*' => Http::response('ok', 200)]);

    app(SlackChannel::class)->send(escalationMessage(), '#incident-response');

    Http::assertSent(fn ($request): bool => $request['channel'] === '#incident-response'
        && $request['text'] === 'Ticket #42 was escalated'
        && str_contains($request['blocks'][0]['text']['text'], 'Production API is unavailable'));
});

it('classifies Slack rate limits and server errors as transient', function (int $status) {
    Http::fake(['*' => Http::response('provider error', $status)]);

    expect(fn () => app(SlackChannel::class)->send(escalationMessage(), '#support'))
        ->toThrow(TransientNotificationException::class);
})->with([429, 500]);

it('classifies Slack client errors as permanent', function () {
    Http::fake(['*' => Http::response('invalid payload', 400)]);

    expect(fn () => app(SlackChannel::class)->send(escalationMessage(), '#support'))
        ->toThrow(PermanentNotificationException::class);
});

it('classifies Slack connection failures as transient', function () {
    Http::fake(fn () => throw new ConnectionException('Connection timed out.'));

    expect(fn () => app(SlackChannel::class)->send(escalationMessage(), '#support'))
        ->toThrow(TransientNotificationException::class);
});

it('supports config-driven simulated transient failures', function () {
    config()->set('notifications.simulate_failure', 'slack');

    try {
        app(SlackChannel::class)->send(escalationMessage(), '#support');
    } catch (TransientNotificationException $exception) {
        expect($exception->errorCode())->toBe('simulated_failure')
            ->and($exception->httpStatus())->toBe(503);

        return;
    }

    $this->fail('The simulated Slack failure was not raised.');
});
