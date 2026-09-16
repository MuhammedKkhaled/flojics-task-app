<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $channel
 * @property string $recipient
 * @property DeliveryStatus $status
 * @property int $attempts
 * @property int $max_attempts
 * @property CarbonImmutable|null $next_attempt_at
 * @property string|null $last_error
 * @property string|null $last_error_code
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $failed_at
 */
class NotificationDelivery extends Model
{
    /** @use HasFactory<NotificationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'channel',
        'recipient',
        'status',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'last_error',
        'last_error_code',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_attempt_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $delivery): void {
            $delivery->uuid ??= (string) Str::uuid();
        });
    }

    /** @return MorphTo<Model, $this> */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<NotificationAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationAttempt::class, 'delivery_id');
    }
}
