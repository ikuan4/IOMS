<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasAuditFields;

class ContractReminder extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'contract_id',
        'days_before_end',
        'is_sent',
        'sent_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'days_before_end' => 'integer',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'contract_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
        'restored_at' => 'datetime',
        'deleted_at' => 'datetime',
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
     *
     * @param Builder<ContractReminder> $query
     * @return Builder<ContractReminder>
     */
    public function scopeUnsent(Builder $query): Builder
    {
        return $query->where('is_sent', false);
    }
}
