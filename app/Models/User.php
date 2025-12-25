<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'last_updated_by',
        'last_updated_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'restored_by',
        'active',
        'email_bounce_count',
        'email_bounced_at',
        'role_id',
        'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'last_updated_at' => 'datetime',
            'last_updated_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
            'deleted_by' => 'integer',
            'restored_by' => 'integer',
            'email_bounced_at' => 'datetime',
            'email_bounce_count' => 'integer',
            'active' => 'boolean',
            'role_id' => 'integer',
            'branch_id' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Automatically hash password when set.
     * Usage: $user->password = 'plain'; (will be hashed)
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value
                ? (Hash::needsRehash($value) ? Hash::make($value) : $value)
                : null
        );
    }
}
