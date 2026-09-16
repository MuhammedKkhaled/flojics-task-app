<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $agent_id
 * @property string $subject
 * @property string $description
 * @property string $priority
 * @property TicketStatus $status
 * @property CarbonImmutable|null $escalated_at
 * @property int|null $escalation_level
 * @property Customer $customer
 * @property Agent|null $agent
 * @property TicketEscalation|null $latestEscalation
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'agent_id',
        'subject',
        'description',
        'priority',
        'status',
        'escalated_at',
        'escalation_level',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'escalated_at' => 'immutable_datetime',
            'escalation_level' => 'integer',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Agent, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /** @return HasMany<TicketEscalation, $this> */
    public function escalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class);
    }

    /** @return HasOne<TicketEscalation, $this> */
    public function latestEscalation(): HasOne
    {
        return $this->hasOne(TicketEscalation::class)->latestOfMany();
    }
}
