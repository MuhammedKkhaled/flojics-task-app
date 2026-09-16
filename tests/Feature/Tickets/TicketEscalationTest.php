<?php

use App\Enums\DeliveryStatus;
use App\Enums\TicketStatus;
use App\Events\TicketEscalated;
use App\Jobs\SendNotificationDeliveryJob;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ChannelManager;
use App\Services\Notifications\DeliveryRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('notifications.simulate_failure');
    config()->set('notifications.channels.email.recipients', ['alerts@example.test']);
    config()->set('notifications.channels.slack.recipient', '#support-escalations');
    config()->set('notifications.default_channels', ['email', 'slack']);
});

it('escalates an eligible ticket and persists pending deliveries without queueing jobs', function () {
    Event::fake([TicketEscalated::class]);
    Queue::fake();

    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Sanctum::actingAs($actor);

    $response = $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email', 'slack'],
        'reason' => 'The customer is blocked from production.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.ticket_id', $ticket->id)
        ->assertJsonPath('data.level', 1)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.reason', 'The customer is blocked from production.')
        ->assertJsonCount(2, 'data.deliveries')
        ->assertJsonPath('data.deliveries.0.status', DeliveryStatus::Pending->value);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Escalated)
        ->and($ticket->escalation_level)->toBe(1)
        ->and($ticket->escalated_at)->not->toBeNull()
        ->and($ticket->escalations)->toHaveCount(1)
        ->and($ticket->escalations->first()->deliveries)->toHaveCount(2);

    Event::assertDispatched(TicketEscalated::class, 1);
    Queue::assertNothingPushed();
});

it('uses configured default channels when channels are omitted', function () {
    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::InProgress]);

    Sanctum::actingAs($actor);

    $this->postJson("/api/tickets/{$ticket->id}/escalate")
        ->assertCreated()
        ->assertJsonCount(2, 'data.deliveries');

    expect($ticket->fresh()->latestEscalation->deliveries->pluck('channel')->sort()->values()->all())
        ->toBe(['email', 'slack']);
});

it('requires authentication', function () {
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertUnauthorized();
});

it('returns not found for an unknown ticket', function () {
    Sanctum::actingAs(User::factory()->agent()->create());

    $this->postJson('/api/tickets/999999/escalate', [
        'channels' => ['email'],
    ])->assertNotFound();
});

it('forbids customers from escalating tickets', function () {
    $customer = User::factory()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Sanctum::actingAs($customer);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertForbidden();
});

it('rejects an unknown notification channel', function () {
    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Sanctum::actingAs($actor);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['sms'],
    ])->assertUnprocessable()->assertJsonValidationErrors('channels.0');
});

it('rejects closed tickets', function () {
    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Closed]);

    Sanctum::actingAs($actor);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertUnprocessable()->assertJsonPath('code', 'ticket_not_escalatable');
});

it('allows only one escalation when competing submissions target the same ticket', function () {
    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Sanctum::actingAs($actor);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertCreated();

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertUnprocessable()->assertJsonPath('code', 'ticket_already_escalated');

    expect($ticket->escalations()->count())->toBe(1);
});

it('keeps the ticket escalated when every notification channel fails', function () {
    Event::fake([TicketEscalated::class]);
    config()->set('notifications.channels.email.recipients', ['invalid-email']);
    config()->set('notifications.channels.slack.webhook_url');

    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
    Sanctum::actingAs(User::factory()->agent()->create());

    $response = $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email', 'slack'],
    ])->assertCreated();

    $escalation = $ticket->fresh()->latestEscalation;

    foreach ($escalation->deliveries as $delivery) {
        $job = (new SendNotificationDeliveryJob(
            deliveryId: $delivery->id,
            channel: $delivery->channel,
            maxAttempts: $delivery->max_attempts,
        ))->withFakeQueueInteractions();

        $job->handle(app(ChannelManager::class), app(DeliveryRecorder::class));
        $job->assertFailed();
    }

    expect($response->json('data.deliveries'))->toHaveCount(2)
        ->and($ticket->fresh()->status)->toBe(TicketStatus::Escalated)
        ->and($escalation->fresh()->status->value)->toBe('failed')
        ->and($escalation->deliveries()->where('status', DeliveryStatus::Failed)->count())->toBe(2);
});

it('returns the ticket with its escalation and deliveries', function () {
    $actor = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

    Sanctum::actingAs($actor);

    $this->postJson("/api/tickets/{$ticket->id}/escalate", [
        'channels' => ['email'],
    ])->assertCreated();

    $this->getJson("/api/tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.status', TicketStatus::Escalated->value)
        ->assertJsonPath('data.escalation.level', 1)
        ->assertJsonCount(1, 'data.escalation.deliveries');
});
