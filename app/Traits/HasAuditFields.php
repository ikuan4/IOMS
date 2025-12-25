<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAuditFields
{
    /**
     * Boot the trait
     */
    protected static function bootHasAuditFields()
    {
        // Set created_by and updated_by on creating
        static::creating(function (Model $model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        // Set updated_by on updating
        static::updating(function (Model $model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        // Set deleted_by when soft deleting
        static::deleting(function (Model $model) {
            if (Auth::check() && $model->getDeletedAtColumn() && !$model->forceDeleting) {
                $model->{$model->getDeletedByColumn()} = Auth::id();
                $model->saveQuietly(); // Save without triggering events again
            }
        });
    }

    /**
     * Get the user who created this record
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this record
     */
    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    /**
     * Get the user who restored this record
     */
    public function restoredBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'restored_by');
    }

    /**
     * Get the deleted_by column name
     */
    public function getDeletedByColumn()
    {
        return 'deleted_by';
    }

    /**
     * Restore the model and set restored_by
     */
    public function restoreWithUser()
    {
        if (Auth::check()) {
            $this->restored_by = Auth::id();

            if (isset($this->name) && str_starts_with($this->name, 'Deleted ')) {
                $this->name = substr($this->name, 8);
            }

            $this->saveQuietly();
        }

        return $this->restore();
    }
}
