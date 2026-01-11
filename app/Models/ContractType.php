<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasAuditFields;

class ContractType extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'code',
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
     * Get the branch that owns this contract type
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all contracts using this type
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Generate unique 3-char code from name
     * Handles duplicates by appending numbers
     */
    public static function generateCode(string $name, int $branchId, ?int $excludeId = null): string
    {
        // Remove spaces and get first 3 chars
        $base = strtoupper(preg_replace('/\s+/', '', $name));
        $base = mb_substr($base, 0, 3);
        
        // Pad if less than 3 chars
        if (mb_strlen($base) < 3) {
            $base = str_pad($base, 3, 'X');
        }

        // Check if code exists in this branch
        $query = static::where('branch_id', $branchId)
            ->where('code', $base);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if (!$query->exists()) {
            return $base;
        }

        // Generate alternative codes
        for ($i = 2; $i <= 99; $i++) {
            $alternative = mb_substr($base, 0, 2) . $i;
            
            $query = static::where('branch_id', $branchId)
                ->where('code', $alternative);
                
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                return $alternative;
            }
        }

        // Fallback to random if all numeric variants taken
        do {
            $random = mb_substr($base, 0, 1) . rand(10, 99);
            
            $query = static::where('branch_id', $branchId)
                ->where('code', $random);
                
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        } while ($query->exists());

        return $random;
    }
}
