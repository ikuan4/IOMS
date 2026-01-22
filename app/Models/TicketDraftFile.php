<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDraftFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_key',
        'stored_file_id',
    ];

    protected $casts = [
        'stored_file_id' => 'integer',
    ];

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function storedFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }
}
