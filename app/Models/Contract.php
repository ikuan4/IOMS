<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasAuditFields;

/**
 * @property int $id
 * @property int $branch_id
 * @property int $contract_type_id
 * @property string $contract_number
 * @property string $contract_with
 * @property int $grace_period_days
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $restored_at
 * @property-read string $status
 * @property-read Branch|null $branch
 * @property-read ContractType|null $contractType
 * @property-read ContractVersion|null $latestVersion
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContractVersion> $versions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContractReminder> $reminders
 * @property-read \Illuminate\Database\Eloquent\Collection<int, NotificationRecipient> $notificationRecipients
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class Contract extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'contract_type_id',
        'contract_number',
        'contract_with',
        'grace_period_days',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'grace_period_days' => 'integer',
        'branch_id' => 'integer',
        'contract_type_id' => 'integer',
        'restored_at' => 'datetime',
    ];

    /**
     * Get the branch that owns this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\ContractType, $this>
     */
    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    /**
     * Get all versions for this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContractVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class);
    }

    /**
     * Get the latest version by version_number
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\ContractVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(ContractVersion::class)->latestOfMany('version_number');
    }

    /**
     * Get all reminders for this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ContractReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(ContractReminder::class);
    }

    /**
     * Get notification recipients for this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\NotificationRecipient, $this>
     */
    public function notificationRecipients(): BelongsToMany
    {
        return $this->belongsToMany(
            NotificationRecipient::class,
            'contract_notification_recipient'
        )->withTimestamps();
    }

    /**
     * Get the user who created this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this contract
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
     |--------------------------------------------------------------------------
     | Computed status attribute (not stored in DB)
     |--------------------------------------------------------------------------
     |
     | Status precedence:
     | 1) Inactive  (is_active = false)
     | 2) Expired   (now > end_date)
     | 3) Expiring Soon (within grace_period_days of end_date)
     | 4) Pending   (now < start_date)
     | 5) Ongoing   (otherwise)
     |
     * 5) Ongoing   (otherwise)
     */
    public function getStatusAttribute(): string
    {
        // 1) Inactive / Archived
        if (!$this->is_active) {
            return 'Inactive';
        }

        $version = $this->latestVersion;

        // Safety: if no version exists, treat as Pending draft-ish
        if (!$version) {
            return 'Pending';
        }

        // Use UTC for comparisons; dates stored as UTC, displayed as IST in views
        $now = Carbon::now('UTC');

        $start = Carbon::parse($version->start_date, 'UTC');
        $end = Carbon::parse($version->end_date, 'UTC');

        // 2) Expired — current date strictly greater than end_date
        if ($now->gt($end)) {
            return 'Expired';
        }

        // 3) Expiring Soon — within grace period window and on/before end_date
        $graceDays = (int) ($this->grace_period_days ?? 0);

        if ($graceDays > 0) {
            $windowStart = $end->copy()->subDays($graceDays);

            if ($now->between($windowStart, $end)) {
                return 'Expiring Soon';
            }
        }

        // 4) Pending — strictly less than start_date
        if ($now->lt($start)) {
            return 'Pending';
        }

        // 5) Default
        return 'Ongoing';
    }

    /**
     * Scope to filter by ongoing contracts
     */
    public function scopeOngoing($query)
    {
        return $query->where('is_active', true);
    }
}
