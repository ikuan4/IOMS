<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasAuditFields;

/**
 * @property int $id
 * @property int $contract_id
 * @property int $version_number
 * @property string|null $description
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $restored_at
 * @property-read Contract|null $contract
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContractVersionFile> $files
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StoredFile> $storedFiles
 */
class ContractVersion extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'contract_id',
        'version_number',
        'description',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'version_number' => 'integer',
        'contract_id' => 'integer',
        'restored_at' => 'datetime',
    ];

    /**
     * Get the contract that owns this version
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get all file pivot records for this version
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContractVersionFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ContractVersionFile::class);
    }

    /**
     * Get stored files through pivot
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\StoredFile, $this>
     */
    public function storedFiles(): BelongsToMany
    {
        return $this->belongsToMany(
            StoredFile::class,
            'contract_version_files'
        )->wherePivotNull('deleted_at')
            ->withPivot('display_order')
            ->withTimestamps();
    }

    /**
     * Check if this is the latest version of the contract
     */
    public function isLatest(): bool
    {
        if (!$this->relationLoaded('contract') || !$this->contract) {
            return false;
        }

        $latest = $this->contract->latestVersion;

        return $latest && $latest->id === $this->id;
    }

    /**
     * Get next version number for a contract
     */
    public static function nextVersionNumberFor(Contract $contract): int
    {
        $max = $contract->versions()->max('version_number');

        return (int) $max + 1;
    }
}
