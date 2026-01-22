<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAuditFields;

class ContractVersionFile extends Model
{
    use SoftDeletes, HasAuditFields;

    protected $fillable = [
        'contract_version_id',
        'stored_file_id',
        'display_order',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'contract_version_id' => 'integer',
        'stored_file_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
        'restored_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the contract version
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ContractVersion, $this>
     */
    public function contractVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class);
    }

    /**
     * Get the stored file
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\StoredFile, $this>
     */
    public function storedFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }
}
