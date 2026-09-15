<?php

namespace App\Models;

use App\Enums\EscalationStatus;
use Database\Factories\TicketEscalationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TicketEscalation extends Model
{
    /** @use HasFactory<TicketEscalationFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'actor_id',
        'level',
        'status',
        'reason',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EscalationStatus::class,
            'level' => 'integer',
            'escalated_at' => 'immutable_datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function deliveries(): MorphMany
    {
        return $this->morphMany(NotificationDelivery::class, 'notifiable');
    }
}
