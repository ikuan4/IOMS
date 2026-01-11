<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersionFile extends Model
{
    protected $fillable = [
        'contract_version_id',
        'stored_file_id',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'contract_version_id' => 'integer',
        'stored_file_id' => 'integer',
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
