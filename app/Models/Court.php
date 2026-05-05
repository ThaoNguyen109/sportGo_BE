<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    /**
     * Fillable attributes for mass assignment
     * SOLID: Single Responsibility - Model defines data, Service/Controller handle access
     */
    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'phone',
        'image',
        'description',
        'open_time',
        'close_time',
        'status'
    ];

    /**
     * Get the owner (User) of this court
     * 
     * Pattern: Relationship Pattern (Eloquent ORM)
     * Reason: Simplify accessing related data without N+1 queries
     * SOLID: Single Responsibility - Model manages relationships
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get all fields (playing areas) of this court
     *
     * Pattern: Relationship Pattern
     * Reason: Structure hierarchical data (Court -> Fields -> BookingDetails)
     * SOLID: Single Responsibility
     */
    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    /**
     * Get all images of this court
     *
     * Pattern: Relationship Pattern
     * Reason: One court can have multiple images
     * SOLID: Single Responsibility
     */
    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class);
    }

    /**
     * Get active fields only
     *
     * Pattern: Query Scope Pattern
     * Reason: Common filter for active fields
     * SOLID: Single Responsibility - Model handles data filtering
     */
    public function activeFields(): HasMany
    {
        return $this->fields()->where('is_active', true);
    }
}
