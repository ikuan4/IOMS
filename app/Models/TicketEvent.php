<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $branch_id
 * @property int|null $actor_id
 * @property string $event_type
 * @property int|null $from_user_id
 * @property int|null $to_user_id
 * @property array<string,mixed>|null $meta
 * @property-read \App\Models\User|null $actor
 * @property-read \App\Models\User|null $fromUser
 * @property-read \App\Models\User|null $toUser
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class TicketEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'branch_id',
        'actor_id',
        'event_type',
        'from_user_id',
        'to_user_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * @param \App\Models\Ticket $ticket
     * @param string $eventType
     * @param array<string,mixed> $meta
     */
    public static function logForTicket(Ticket $ticket, string $eventType, array $meta = []): self
    {
        return self::create([
            'ticket_id' => (int) $ticket->getKey(),
            'branch_id' => (int) ($ticket->branch_id ?? 0),
            'actor_id' => auth()->id(),
            'event_type' => $eventType,
            'meta' => $meta,
        ]);
    }
}
