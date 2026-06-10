<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'refund_amount',
        'reason',
        'status',
        'admin_note',
        'refunded_at'
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime'
    ];

    /**
     * Mỗi yêu cầu hoàn tiền thuộc về một đơn đặt sân (Booking)
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Mỗi yêu cầu hoàn tiền thuộc về một khách hàng (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
