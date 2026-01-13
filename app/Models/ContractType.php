<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasAuditFields;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property string|null $description
 * @property string $code
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
class ContractType extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'code',
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
     * Get the branch that owns this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all contracts using this type
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the user who created this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who restored this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    /**
     * Generate unique 3-char code from name
     * Handles duplicates by appending numbers
     */
    public static function generateCode(string $name, int $branchId, ?int $excludeId = null): string
    {
        // Remove spaces and get first 3 chars
        $normalized = preg_replace('/\s+/', '', $name);
        if ($normalized === null) {
            $normalized = $name;
        }
        $base = strtoupper($normalized);
        $base = mb_substr($base, 0, 3);

        // Pad if less than 3 chars
        if (mb_strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        // Check if code exists in this branch
        $query = static::where('branch_id', $branchId)
            ->where('code', $base);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if (!$query->exists()) {
            return $base;
        }

        // Generate alternative codes
        for ($i = 2; $i <= 99; $i++) {
            $alternative = mb_substr($base, 0, 2) . $i;

            $query = static::where('branch_id', $branchId)
                ->where('code', $alternative);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $alternative;
            }
        }

        // Fallback to random if all numeric variants taken
        do {
            $random = mb_substr($base, 0, 1) . rand(10, 99);

            $query = static::where('branch_id', $branchId)
                ->where('code', $random);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        } while ($query->exists());

        return $random;
    }

    /**
     * Scope to filter only active contract types
     *
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by branch
     *
     * @param \Illuminate\Database\Eloquent\Builder<$this> $query
     * @param int $branchId
     * @return \Illuminate\Database\Eloquent\Builder<$this>
     */
    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Check if contract type is active
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Count active contracts using this type
     */
    public function activeContractCount(): int
    {
        return $this->contracts()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();
    }
}
