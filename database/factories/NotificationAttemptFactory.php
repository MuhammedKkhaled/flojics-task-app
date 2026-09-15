<?php

namespace Database\Factories;

use App\Models\NotificationAttempt;
use App\Models\NotificationDelivery;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NotificationAttempt> */
class NotificationAttemptFactory extends Factory
{
    protected $model = NotificationAttempt::class;

    public function definition(): array
    {
        return [
            'delivery_id' => NotificationDelivery::factory(),
            'attempt_number' => 1,
            'outcome' => 'sent',
            'error_class' => null,
            'error_code' => null,
            'http_status' => 200,
            'duration_ms' => fake()->numberBetween(20, 500),
        ];
    }
}
