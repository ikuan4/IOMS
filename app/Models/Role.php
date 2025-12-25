<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_protected',
        'branch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_protected' => 'boolean',
        'branch_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected static function booted()
    {
        static::deleting(function ($role) {
            if (($role->is_protected ?? false) || ($role->name ?? '') === 'Developer') {
                throw new \Exception('This role is protected and cannot be deleted.');
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
