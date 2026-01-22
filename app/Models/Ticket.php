<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $ticket_type_id
 * @property int $ticket_module_id
 * @property string $subject
 * @property string|null $description
 * @property string $status
 * @property string $priority
 * @property int|null $assigned_to
 * @property \Illuminate\Support\Carbon|null $due_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $restored_at
 */
class Ticket extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected static ?string $cachedSubjectColumn = null;
    protected static ?string $cachedAssigneeColumn = null;
    protected static ?string $cachedDueAtColumn = null;
    protected static ?bool $cachedHasTicketNumber = null;
    protected static ?bool $cachedHasReporterId = null;

    protected $fillable = [
        'branch_id',
        'ticket_type_id',
        'ticket_module_id',
        // keep legacy + current schema compatible
        'ticket_number',
        'subject',
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'assignee_id',
        'due_at',
        'sla_due_at',
        'reporter_id',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'ticket_type_id' => 'integer',
        'ticket_module_id' => 'integer',
        'assigned_to' => 'integer',
        'assignee_id' => 'integer',
        'reporter_id' => 'integer',
        'due_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            if (self::hasTicketNumberColumn() && empty($ticket->getAttribute('ticket_number'))) {
                $ticket->setAttribute('ticket_number', self::generateTicketNumber());
            }

            if (self::hasReporterIdColumn() && empty($ticket->getAttribute('reporter_id')) && auth()->check()) {
                $ticket->setAttribute('reporter_id', auth()->id());
            }
        });
    }

    public static function subjectColumn(): string
    {
        if (self::$cachedSubjectColumn !== null) {
            return self::$cachedSubjectColumn;
        }

        self::$cachedSubjectColumn = Schema::hasColumn('tickets', 'title') ? 'title' : 'subject';
        return self::$cachedSubjectColumn;
    }

    public static function assigneeForeignKey(): string
    {
        if (self::$cachedAssigneeColumn !== null) {
            return self::$cachedAssigneeColumn;
        }

        self::$cachedAssigneeColumn = Schema::hasColumn('tickets', 'assignee_id') ? 'assignee_id' : 'assigned_to';
        return self::$cachedAssigneeColumn;
    }

    public static function dueAtColumn(): string
    {
        if (self::$cachedDueAtColumn !== null) {
            return self::$cachedDueAtColumn;
        }

        if (Schema::hasColumn('tickets', 'sla_due_at')) {
            self::$cachedDueAtColumn = 'sla_due_at';
        } else {
            self::$cachedDueAtColumn = 'due_at';
        }

        return self::$cachedDueAtColumn;
    }

    public static function hasTicketNumberColumn(): bool
    {
        if (self::$cachedHasTicketNumber !== null) {
            return self::$cachedHasTicketNumber;
        }

        self::$cachedHasTicketNumber = Schema::hasColumn('tickets', 'ticket_number');
        return self::$cachedHasTicketNumber;
    }

    public static function hasReporterIdColumn(): bool
    {
        if (self::$cachedHasReporterId !== null) {
            return self::$cachedHasReporterId;
        }

        self::$cachedHasReporterId = Schema::hasColumn('tickets', 'reporter_id');
        return self::$cachedHasReporterId;
    }

    protected static function generateTicketNumber(): string
    {
        return 'TKT-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    public function getSubjectAttribute(): ?string
    {
        $col = self::subjectColumn();
        return $this->getAttribute($col);
    }

    public function setSubjectAttribute(?string $value): void
    {
        $col = self::subjectColumn();
        $this->attributes[$col] = $value;
    }

    public function getAssignedToAttribute(): ?int
    {
        $col = self::assigneeForeignKey();
        $val = $this->getAttribute($col);
        return $val === null ? null : (int) $val;
    }

    public function setAssignedToAttribute(int|string|null $value): void
    {
        $col = self::assigneeForeignKey();
        $this->attributes[$col] = $value === '' ? null : $value;
    }

    public function getDueAtAttribute(): mixed
    {
        $col = self::dueAtColumn();
        return $this->getAttribute($col);
    }

    public function setDueAtAttribute(mixed $value): void
    {
        $col = self::dueAtColumn();
        $this->attributes[$col] = $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\TicketModule, $this>
     */
    public function ticketModule(): BelongsTo
    {
        return $this->belongsTo(TicketModule::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, self::assigneeForeignKey());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\TicketFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }
}
