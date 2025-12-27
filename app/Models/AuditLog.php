<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\AuditLogFactory>
 */
class AuditLog extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
    ];

    /**
     * Get the user who performed the action.
     */
    // BelongsTo relation user
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an audit entry.
     *
     * @param string $action
     * @param \Illuminate\Database\Eloquent\Model|null $auditable
     * @param array<string,mixed> $oldValues
     * @param array<string,mixed> $newValues
     * @return self
     */
    public static function log(string $action, ?Model $auditable = null, array $oldValues = [], array $newValues = []): self
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
