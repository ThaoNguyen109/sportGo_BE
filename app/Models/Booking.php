<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_price',
        'payment_method',
        'status',
        'cancel_reason',
        'cancelled_by',
    ];

    /**
     * Người đặt sân
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Danh sách sân đã đặt
     */
    public function details(): HasMany
    {
        return $this->hasMany(BookingDetail::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Payout
    |--------------------------------------------------------------------------
    */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(
            OwnerPayout::class,
            'payout_id'
        );
    }

}