<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasAuditFields;

class StoredFile extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'sha256',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'branch_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
        'restored_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the branch that owns this file
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all version file pivot records
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContractVersionFile, $this>
     */
    public function contractVersionFiles(): HasMany
    {
        return $this->hasMany(ContractVersionFile::class);
    }

    /**
     * Get contract versions through pivot
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\ContractVersion, $this>
     */
    public function contractVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            ContractVersion::class,
            'contract_version_files'
        )->wherePivotNull('deleted_at')
            ->withPivot('display_order')
            ->withTimestamps();
    }

    /**
     * Get download filename
     */
    public function getDownloadNameAttribute(): string
    {
        return $this->original_filename ?? basename($this->path);
    }
}
