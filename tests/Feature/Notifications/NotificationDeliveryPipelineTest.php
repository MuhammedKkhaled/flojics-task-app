<?php

use App\Enums\DeliveryStatus;
use App\Enums\EscalationStatus;
use App\Events\NotificationDeliveryFailed;
use App\Events\TicketEscalated;
use App\Jobs\SendNotificationDeliveryJob;
use App\Listeners\DispatchEscalationNotifications;
use App\Models\NotificationDelivery;
use App\Models\TicketEscalation;
use App\Notifications\ChannelManager;
use App\Services\Notifications\DeliveryRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function pipelineDelivery(string $channel = 'slack', ?TicketEscalation $escalation = null): NotificationDelivery
{
    $escalation ??= TicketEscalation::factory()->create();

    return NotificationDelivery::factory()->create([
        'notifiable_type' => $escalation->getMorphClass(),
        'notifiable_id' => $escalation->id,
        'channel' => $channel,
        'recipient' => $channel === 'email' ? 'alerts@example.test' : '#support',
    ]);
}

function runDeliveryJob(NotificationDelivery $delivery): SendNotificationDeliveryJob
{
    $job = (new SendNotificationDeliveryJob(
        deliveryId: $delivery->id,
        channel: $delivery->channel,
        maxAttempts: $delivery->max_attempts,
    ))->withFakeQueueInteractions();

    $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));

    return $job;
}

beforeEach(function () {
    config()->set('notifications.simulate_failure');
    config()->set('notifications.channels.slack.webhook_url', 'https://hooks.slack.test/services/example');
    config()->set('notifications.retry.backoff', [10, 60, 300]);
    config()->set('notifications.retry.jitter', 0);
});

it('dispatches one isolated job per pending delivery', function () {
    Queue::fake();

    $escalation = TicketEscalation::factory()->create();
    pipelineDelivery('email', $escalation);
    pipelineDelivery('slack', $escalation);

    app(DispatchEscalationNotifications::class)->handle(new TicketEscalated($escalation));

    Queue::assertPushed(SendNotificationDeliveryJob::class, 2);
});

it('configures four attempts, jittered backoff middleware and timeout', function () {
    $delivery = pipelineDelivery();
    $job = new SendNotificationDeliveryJob($delivery->id, $delivery->channel, 4);

    expect($job->tries)->toBe(4)
        ->and($job->timeout)->toBe(15)
        ->and($job->backoff())->toBe([10, 60, 300])
        ->and($job->middleware()[0])->toBeInstanceOf(WithoutOverlapping::class)
        ->and($job->middleware()[1])->toBeInstanceOf(RateLimited::class);
});

it('records a successful email delivery and completes its escalation', function () {
    Mail::fake();
    $delivery = pipelineDelivery('email');

    runDeliveryJob($delivery);

    $delivery->refresh();

    expect($delivery->status)->toBe(DeliveryStatus::Sent)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->attempts()->first()->outcome)->toBe('sent')
        ->and($delivery->notifiable->fresh()->status)->toBe(EscalationStatus::Completed);
});

it('records a transient failure and releases it with backoff', function () {
    Http::fake(['*' => Http::response('unavailable', 500)]);
    $delivery = pipelineDelivery();

    $job = runDeliveryJob($delivery);
    $job->assertReleased(10)->assertNotFailed();

    $delivery->refresh();

    expect($delivery->status)->toBe(DeliveryStatus::Pending)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->next_attempt_at)->not->toBeNull()
        ->and($delivery->attempts()->first()->outcome)->toBe('transient_failure')
        ->and($delivery->attempts()->first()->http_status)->toBe(500);
});

it('can succeed after transient retries with every attempt recorded', function () {
    Http::fakeSequence()
        ->push('unavailable', 500)
        ->push('unavailable', 500)
        ->push('ok', 200);

    $delivery = pipelineDelivery();
    $job = new SendNotificationDeliveryJob($delivery->id, $delivery->channel, 4);
    $job->withFakeQueueInteractions();

    $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));
    $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));
    $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));

    $delivery->refresh();

    expect($delivery->status)->toBe(DeliveryStatus::Sent)
        ->and($delivery->attempts)->toBe(3)
        ->and($delivery->attempts()->pluck('outcome')->all())
        ->toBe(['transient_failure', 'transient_failure', 'sent']);
});

it('fails immediately after a permanent provider error', function () {
    Event::fake([NotificationDeliveryFailed::class]);
    Http::fake(['*' => Http::response('invalid payload', 400)]);
    $delivery = pipelineDelivery();

    $job = runDeliveryJob($delivery);
    $job->assertFailed();

    $delivery->refresh();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->attempts)->toBe(1)
        ->and($delivery->last_error_code)->toBe('slack_request_rejected')
        ->and($delivery->attempts()->first()->outcome)->toBe('permanent_failure')
        ->and($delivery->notifiable->fresh()->status)->toBe(EscalationStatus::Failed);

    Event::assertDispatched(NotificationDeliveryFailed::class, 1);
});

it('fails after exactly four transient attempts', function () {
    Event::fake([NotificationDeliveryFailed::class]);
    Http::fake(['*' => Http::response('unavailable', 500)]);
    $delivery = pipelineDelivery();
    $job = new SendNotificationDeliveryJob($delivery->id, $delivery->channel, 4);
    $job->withFakeQueueInteractions();

    foreach (range(1, 4) as $_attempt) {
        $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));
    }

    $job->assertFailed();
    $delivery->refresh();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->attempts)->toBe(4)
        ->and($delivery->attempts()->count())->toBe(4)
        ->and($delivery->failed_at)->not->toBeNull();

    Event::assertDispatched(NotificationDeliveryFailed::class, 1);
});

it('does not send or record another attempt for an already sent delivery', function () {
    Http::fake(fn () => throw new RuntimeException('HTTP should not be called.'));

    $delivery = pipelineDelivery();
    $delivery->update([
        'status' => DeliveryStatus::Sent,
        'attempts' => 1,
        'sent_at' => now(),
    ]);
    $delivery->attempts()->create([
        'attempt_number' => 1,
        'outcome' => 'sent',
        'duration_ms' => 1,
    ]);

    runDeliveryJob($delivery);

    expect($delivery->attempts()->count())->toBe(1);
    Http::assertNothingSent();
});

it('rolls a mixed delivery result up as partially failed', function () {
    Mail::fake();
    Http::fake(['*' => Http::response('invalid payload', 400)]);

    $escalation = TicketEscalation::factory()->create();
    $email = pipelineDelivery('email', $escalation);
    $slack = pipelineDelivery('slack', $escalation);

    runDeliveryJob($email);
    runDeliveryJob($slack);

    expect($escalation->fresh()->status)->toBe(EscalationStatus::PartiallyFailed);
});

it('requeues stuck pending deliveries through the reconciliation command', function () {
    Queue::fake();

    $delivery = pipelineDelivery();
    DB::table('notification_deliveries')
        ->where('id', $delivery->id)
        ->update(['updated_at' => now()->subMinutes(10)]);

    $this->artisan('notifications:reconcile', ['--minutes' => 5])
        ->expectsOutput('Requeued 1 pending notification deliveries.')
        ->assertSuccessful();

    Queue::assertPushed(
        SendNotificationDeliveryJob::class,
        fn (SendNotificationDeliveryJob $job): bool => $job->deliveryId === $delivery->id,
    );
});
