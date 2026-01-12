<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasAuditFields;

class NotificationRecipient extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'name',
        'designation',
        'email',
        'mobile',
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
     * Get the branch that owns this recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get contracts assigned to this recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Contract, $this>
     */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(
            Contract::class,
            'contract_notification_recipient'
        )->withTimestamps();
    }

    /**
     * Get the user who created this notification recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this notification recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this notification recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who restored this notification recipient
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }
}
