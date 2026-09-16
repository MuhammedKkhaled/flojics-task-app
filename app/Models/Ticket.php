<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class);
    }

    public function latestEscalation(): HasOne
    {
        return $this->hasOne(TicketEscalation::class)->latestOfMany();
    }
}
