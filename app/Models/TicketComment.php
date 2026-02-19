<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $user_id
 * @property bool $is_internal
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class TicketComment extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected static ?string $cachedBodyColumn = null;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_internal',
        'body',
        'comment',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public static function bodyColumn(): string
    {
        if (self::$cachedBodyColumn !== null) {
            return self::$cachedBodyColumn;
        }

        if (Schema::hasColumn('ticket_comments', 'body')) {
            self::$cachedBodyColumn = 'body';
        } elseif (Schema::hasColumn('ticket_comments', 'comment')) {
            self::$cachedBodyColumn = 'comment';
        } elseif (Schema::hasColumn('ticket_comments', 'message')) {
            self::$cachedBodyColumn = 'message';
        } elseif (Schema::hasColumn('ticket_comments', 'content')) {
            self::$cachedBodyColumn = 'content';
        } else {
            self::$cachedBodyColumn = 'body';
        }

        return self::$cachedBodyColumn;
    }

    public function getBodyAttribute($value): ?string
    {
        $col = self::bodyColumn();

        if ($col === 'body') {
            return $value;
        }

        return $this->attributes[$col] ?? $value;
    }

    public function setBodyAttribute(?string $value): void
    {
        $this->attributes[self::bodyColumn()] = $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TicketCommentFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(TicketCommentFile::class, 'ticket_comment_id');
    }
}
