<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCommentDraftFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'draft_key',
        'stored_file_id',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'stored_file_id' => 'integer',
    ];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<StoredFile, $this>
     */
    public function storedFile(): BelongsTo
    {
        return $this->belongsTo(StoredFile::class);
    }
}
