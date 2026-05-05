<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'order_id',
        'transaction_id',
        'request_id',
        'amount',
        'payment_method',
        'status',
        'raw_response',
        'paid_at',
    ];

    protected $casts = [
        'amount'   => 'decimal:2',
        'paid_at'  => 'datetime',
        'raw_response' => 'array', // tự động json_encode/decode
    ];

    // Một payment thuộc về một booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}