<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'days_before_end',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'days_before_end' => 'integer',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'contract_id' => 'integer',
    ];

    /**
     * Get the contract that owns this reminder
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Scope to filter unsent reminders
     */
    public function scopeUnsent($query)
    {
        return $query->where('is_sent', false);
    }
}
