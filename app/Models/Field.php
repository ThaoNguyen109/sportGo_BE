<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'name',
        'is_active'
    ];

    /**
     * Get the court this field belongs to
     *
     * Pattern: Inverse Relationship
     * SOLID: Single Responsibility - Model manages own relationships
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get all price tiers for this field
     *
     * Pattern: Relationship Pattern
     * Reason: One field can have multiple price tiers (by time/day)
     * SOLID: Single Responsibility
     */
    public function prices()
    {
        return $this->hasMany(FieldPrice::class);
    }

    /**
     * Get current price (active pricing)
     *
     * Pattern: Query Scope Pattern
     * Reason: Get current pricing for booking calculations
     * SOLID: Single Responsibility
     */
    public function currentPrices()
    {
        return $this->prices()
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time');
    }
}
