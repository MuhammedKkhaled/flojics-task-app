<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Database\Factories\NotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

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

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationAttempt::class, 'delivery_id');
    }
}
