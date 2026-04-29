<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Field extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     */
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
}
