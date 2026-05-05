<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    // Một booking thuộc về một user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Một booking có nhiều booking_details (nhiều slot)
    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }
}
