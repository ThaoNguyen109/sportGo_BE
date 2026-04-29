<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtImage extends Model
{
    use HasFactory;

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'court_id',
        'image_url'
    ];

    /**
     * Get the court this image belongs to
     * 
     * Pattern: Inverse Relationship
     * SOLID: Single Responsibility
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
