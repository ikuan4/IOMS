<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $restored_at
 * @property-read \App\Models\Branch|null $branch
 */
class TicketType extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'branch_id' => 'integer',
        'restored_at' => 'datetime',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
