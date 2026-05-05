<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'field_id',
        'booking_date',
        'start_time',
        'end_time',
        'price',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'price'        => 'decimal:2',
    ];

    // Một detail thuộc về một booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    // Một detail thuộc về một field (sân con)
    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }
}