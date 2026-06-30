<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'is_confirmed',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
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

    /**
     * Yêu cầu hoàn tiền liên quan đến Booking này
     */
    public function refundRequest(): HasOne
    {
        return $this->hasOne(RefundRequest::class);
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