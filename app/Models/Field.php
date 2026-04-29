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
     * Sân cha
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Danh sách giá theo khung giờ
     */
    public function prices(): HasMany
    {
        return $this->hasMany(FieldPrice::class);
    }
}