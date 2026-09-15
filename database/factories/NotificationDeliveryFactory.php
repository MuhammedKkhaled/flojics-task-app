<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\TicketEscalation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<NotificationDelivery> */
class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'notifiable_type' => TicketEscalation::class,
            'notifiable_id' => TicketEscalation::factory(),
            'channel' => 'email',
            'recipient' => fake()->unique()->safeEmail(),
            'status' => DeliveryStatus::Pending,
            'attempts' => 0,
            'max_attempts' => (int) config('notifications.retry.max_attempts', 4),
        ];
    }

    public function preExhausted(): static
    {
        $maxAttempts = (int) config('notifications.retry.max_attempts', 4);

        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Failed,
            'attempts' => $maxAttempts,
            'max_attempts' => $maxAttempts,
            'last_error' => 'Provider remained unavailable after all attempts.',
            'last_error_code' => 'provider_unavailable',
            'failed_at' => now(),
        ]);
    }
}
