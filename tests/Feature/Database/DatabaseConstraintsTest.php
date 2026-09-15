<?php

use App\Enums\DeliveryStatus;
use App\Models\NotificationAttempt;
use App\Models\NotificationDelivery;
use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects duplicate escalation levels for one ticket', function () {
    $ticket = Ticket::factory()->create();
    $actor = User::factory()->agent()->create();

    TicketEscalation::factory()->create([
        'ticket_id' => $ticket->id,
        'actor_id' => $actor->id,
        'level' => 1,
    ]);

    expect(fn () => TicketEscalation::factory()->create([
        'ticket_id' => $ticket->id,
        'actor_id' => $actor->id,
        'level' => 1,
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate channel recipients for one notifiable', function () {
    $escalation = TicketEscalation::factory()->create();

    NotificationDelivery::factory()->create([
        'notifiable_type' => $escalation->getMorphClass(),
        'notifiable_id' => $escalation->id,
        'channel' => 'email',
        'recipient' => 'support@example.test',
    ]);

    expect(fn () => NotificationDelivery::factory()->create([
        'notifiable_type' => $escalation->getMorphClass(),
        'notifiable_id' => $escalation->id,
        'channel' => 'email',
        'recipient' => 'support@example.test',
    ]))->toThrow(QueryException::class);
});

it('rejects duplicate attempt numbers for one delivery', function () {
    $delivery = NotificationDelivery::factory()->create();

    NotificationAttempt::factory()->create([
        'delivery_id' => $delivery->id,
        'attempt_number' => 1,
    ]);

    expect(fn () => NotificationAttempt::factory()->create([
        'delivery_id' => $delivery->id,
        'attempt_number' => 1,
    ]))->toThrow(QueryException::class);
});

it('enforces the delivery attempt ceiling', function () {
    expect(fn () => NotificationDelivery::factory()->create([
        'attempts' => 5,
        'max_attempts' => 4,
    ]))->toThrow(QueryException::class);
});

it('provides a pre-exhausted delivery factory state', function () {
    $delivery = NotificationDelivery::factory()->preExhausted()->create();

    expect($delivery->status)->toBe(DeliveryStatus::Failed)
        ->and($delivery->attempts)->toBe(4)
        ->and($delivery->failed_at)->not->toBeNull();
});
