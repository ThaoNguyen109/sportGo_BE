<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FieldPrice Model
 *
 * Pattern: Eloquent Model Pattern
 * Reason: Standard Laravel ORM for database interaction
 *
 * SOLID: Single Responsibility - ONLY handles FieldPrice data and relationships
 * Does NOT: handle business logic, HTTP responses, or complex queries
 */
class FieldPrice extends Model
{
    use HasFactory;

    /**
     * Fillable attributes for mass assignment
     * SOLID: Single Responsibility - Model defines data structure
     */
    protected $fillable = [
        'field_id',
        'day_of_week', // 1=Monday, 7=Sunday
        'start_time',
        'end_time',
        'price',
        'is_active'
    ];

    /**
     * Cast attributes to correct types
     * SOLID: Single Responsibility - Model handles data types
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
    ];

    /**
     * Get the field this price belongs to
     *
     * Pattern: Inverse Relationship
     * SOLID: Single Responsibility - Model manages relationships
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    /**
     * Get the court through field relationship
     *
     * Pattern: Has-One-Through Relationship
     * Reason: Access court data from price (FieldPrice -> Field -> Court)
     * SOLID: Single Responsibility
     */
    public function court(): BelongsTo
    {
        return $this->hasOneThrough(Court::class, Field::class, 'id', 'id', 'field_id', 'court_id');
    }

    /**
     * Scope for active prices only
     *
     * Pattern: Query Scope Pattern
     * Reason: Common filter for active pricing
     * SOLID: Single Responsibility
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific day of week
     *
     * Pattern: Query Scope Pattern
     * Reason: Filter prices by day
     * SOLID: Single Responsibility
     */
    public function scopeForDay($query, int $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    /**
     * Scope for time range
     *
     * Pattern: Query Scope Pattern
     * Reason: Filter prices by time slot
     * SOLID: Single Responsibility
     */
    public function scopeForTimeRange($query, string $startTime, string $endTime)
    {
        return $query->where('start_time', '<=', $startTime)
                    ->where('end_time', '>=', $endTime);
    }
}
