<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\User;
use App\Models\Field;
use App\Models\CourtImage;

class Court extends Model
{
    use HasFactory;

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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class);
    }
}