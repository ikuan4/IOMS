<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property int|null $restored_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $restored_at
 *
 * @use \Illuminate\Database\Eloquent\Factories\HasFactory<\Database\Factories\BranchFactory>
 */
class Branch extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'restored_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_by' => 'integer',
        'restored_by' => 'integer',
    ];

    // BelongsTo relation creator
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function creator() : BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // BelongsTo relation updater
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updater() : BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // HasMany relation roles (branch-specific roles only)
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Role, $this>
     */
    public function roles() : HasMany
    {
        return $this->hasMany(Role::class, 'branch_id')->where('is_global', false);
    }

    // BelongsToMany relation users (via branch_user pivot)
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\User, $this>
     */
    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user', 'branch_id', 'user_id')
            ->withTimestamps();
    }
}
