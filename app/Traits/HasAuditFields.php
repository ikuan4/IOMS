<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait HasAuditFields
{
    /**
     * Cache of table/column existence checks.
     *
     * @var array<string, bool>
     */
    protected static array $auditColumnExistsCache = [];

    protected static function auditColumnExists(Model $model, string $column): bool
    {
        $table = $model->getTable();
        $key = $table . ':' . $column;

        if (array_key_exists($key, self::$auditColumnExistsCache)) {
            return self::$auditColumnExistsCache[$key];
        }

        try {
            return self::$auditColumnExistsCache[$key] = Schema::hasColumn($table, $column);
        } catch (\Throwable $__e) {
            return self::$auditColumnExistsCache[$key] = false;
        }
    }

    /**
     * Boot the trait
     */
    protected static function bootHasAuditFields(): void
    {
        // Set created_by and updated_by on creating
        static::creating(function (Model $model) {
            if (Auth::check()) {
                if (self::auditColumnExists($model, 'created_by')) {
                    $model->setAttribute('created_by', Auth::id());
                }
                if (self::auditColumnExists($model, 'updated_by')) {
                    $model->setAttribute('updated_by', Auth::id());
                }
            }
        });

        // Set updated_by on updating
        static::updating(function (Model $model) {
            if (Auth::check()) {
                if (self::auditColumnExists($model, 'updated_by')) {
                    $model->setAttribute('updated_by', Auth::id());
                }
            }
        });

        // Set deleted_by when soft deleting
        static::deleting(function (Model $model) {
            if (!Auth::check()) {
                return;
            }

            // Only attempt this when the model provides deleted column helpers
            if (!method_exists($model, 'getDeletedAtColumn') || !method_exists($model, 'getDeletedByColumn')) {
                return;
            }

            $deletedAtColumn = null;
            if (method_exists($model, 'getDeletedAtColumn')) {
                try {
                    $deletedAtColumn = $model->getDeletedAtColumn();
                } catch (\Throwable $__e) {
                    $deletedAtColumn = null;
                }
            }

            if ($deletedAtColumn) {
                // Check if this is a force delete operation
                $forceDeleting = method_exists($model, 'isForceDeleting') ? $model->isForceDeleting() : false;
                if (!$forceDeleting) {
                    $deletedByColumn = $model->getDeletedByColumn();
                    if (is_string($deletedByColumn) && self::auditColumnExists($model, $deletedByColumn)) {
                        $model->setAttribute($deletedByColumn, (int) Auth::id());
                        $model->saveQuietly(); // Save without triggering events again
                    }
                }
            }
        });
    }

    /**
     * Get the user who created this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    /**
     * Get the user who restored this record
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'restored_by');
    }

    /**
     * Get the deleted_by column name
     */
    public function getDeletedByColumn(): string
    {
        return 'deleted_by';
    }

    /**
     * Restore the model and set restored_by
     */
    public function restoreWithUser(): ?bool
    {
        if (Auth::check()) {
            if (self::auditColumnExists($this, 'restored_by')) {
                $this->setAttribute('restored_by', (int) Auth::id());
            }

            if (isset($this->name) && str_starts_with($this->name, 'Deleted ')) {
                $this->setAttribute('name', substr($this->name, 8));
            }

            // set restored_at when column exists
            try {
                if (Schema::hasColumn($this->getTable(), 'restored_at')) {
                    $this->setAttribute('restored_at', now());
                }
            } catch (\Throwable $__e) {
                // ignore schema checks on broken environments
            }

            $this->saveQuietly();
        }

        return $this->restore();
    }
}
