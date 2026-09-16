<?php

namespace App\Models;

use App\Enums\EscalationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TicketEscalationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $actor_id
 * @property int $level
 * @property EscalationStatus $status
 * @property string|null $reason
 * @property CarbonImmutable $escalated_at
 * @property Ticket $ticket
 * @property User $actor
 */
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

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return MorphMany<NotificationDelivery, $this> */
    public function deliveries(): MorphMany
    {
        return $this->morphMany(NotificationDelivery::class, 'notifiable');
    }
}
