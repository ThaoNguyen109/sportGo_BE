<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerPayout extends Model
{
    protected $fillable = [

        'owner_id',

        'gross_amount',

        'commission_percent',

        'commission_amount',

        'net_amount',

        'status',

        'note',

        'paid_at'
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'gross_amount' => 'float',

        'commission_percent' => 'float',

        'commission_amount' => 'float',

        'net_amount' => 'float',

        'paid_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | Owner
    |--------------------------------------------------------------------------
    */

    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Bookings thuộc payout
    |--------------------------------------------------------------------------
    */

    public function bookings(): HasMany
    {
        return $this->hasMany(
            Booking::class,
            'payout_id'
        );
    }
}