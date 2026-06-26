<?php

namespace App\Services;

use App\Models\OwnerPayout;

class OwnerPayoutService
{
    
    /**
     * Danh sách payout của owner
     */
    public function getOwnerPayouts(
        int $ownerId,
        array $filters = []
    )
    {
        $query = OwnerPayout::query()

            ->with([

                'bookings',

                'bookings.user:id,name',

                'owner:id,name,email'
            ])

            ->where(
                'owner_id',
                $ownerId
            );

        /*
        |--------------------------------------------------------------------------
        | Filter status
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {

            $query->where(
                'status',
                $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter from date
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['from_date'])) {

            $query->whereDate(
                'created_at',
                '>=',
                $filters['from_date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter to date
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['to_date'])) {

            $query->whereDate(
                'created_at',
                '<=',
                $filters['to_date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sort
        |--------------------------------------------------------------------------
        */

        $query

            ->orderByRaw("
                CASE
                    WHEN status = 'pending'
                    THEN 0
                    ELSE 1
                END
            ")

            ->latest();

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = $filters['per_page'] ?? 10;

        return $query->paginate($perPage);
    }
}