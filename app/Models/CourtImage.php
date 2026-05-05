<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'court_id',
        'image_url'
    ];

    /**
     * Ảnh thuộc về sân nào
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}