<?php

namespace App\Models;

use App\Enums\AgentTeam;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property AgentTeam $team
 * @property User $user
 */
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team',
    ];

    protected function casts(): array
    {
        return [
            'team' => AgentTeam::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
