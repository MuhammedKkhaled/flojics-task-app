<?php

namespace App\Models;

use Database\Factories\NotificationAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationAttempt extends Model
{
    /** @use HasFactory<NotificationAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'attempt_number',
        'outcome',
        'error_class',
        'error_code',
        'http_status',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'http_status' => 'integer',
            'duration_ms' => 'integer',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(NotificationDelivery::class, 'delivery_id');
    }
}
