<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StoredFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'sha256',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'branch_id' => 'integer',
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
        )->withPivot('display_order')
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
