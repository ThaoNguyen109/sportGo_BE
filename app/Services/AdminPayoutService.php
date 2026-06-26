<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\OwnerPayout;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Strategies\Payout\PayoutStrategy;
use App\Events\PayoutPaidEvent;


class AdminPayoutService
{
    public function __construct(
        private PayoutStrategy $payoutStrategy
    ) {}

    

    /*
    |--------------------------------------------------------------------------
    | BOOKING ELIGIBLE
    |--------------------------------------------------------------------------
    */

    /**
     * Booking đủ điều kiện payout theo owner
     */
    private function getEligibleBookingsByOwner(
        int $ownerId
    )
    {
        return Booking::query()

            ->with([

                'details',

                'details.field.court',

                'user:id,name'
            ])

            ->join(
                'booking_details',
                'bookings.id',
                '=',
                'booking_details.booking_id'
            )

            ->join(
                'fields',
                'booking_details.field_id',
                '=',
                'fields.id'
            )

            ->join(
                'courts',
                'fields.court_id',
                '=',
                'courts.id'
            )

            ->where(
                'courts.owner_id',
                $ownerId
            )

            /*
            |--------------------------------------------------------------------------
            | Điều kiện payout
            |--------------------------------------------------------------------------
            */

            ->where(
                'bookings.status',
                'paid'
            )

            ->whereNull(
                'bookings.payout_id'
            )

            ->whereDate(
                'booking_details.booking_date',
                '<=',
                now()->subDays(2)
            )

            /*
            |--------------------------------------------------------------------------
            | Select
            |--------------------------------------------------------------------------
            */

            ->select('bookings.*')

            ->distinct()

            ->get();
    }
    public function getAllOwnersPendingPayout()
{
    $owners = User::query()

        ->where('role', 'owner')

        ->get();

    return $owners->map(function ($owner) {

        $bookings = $this
            ->getEligibleBookingsByOwner(
                $owner->id
            );

        $payoutResult = $this
            ->payoutStrategy
            ->calculate($bookings);

        return [

            'owner_id' => $owner->id,

            'owner_name' => $owner->name,

            'email' => $owner->email,

            'phone' => $owner->phone,

            'pending_bookings' =>
                $bookings->count(),

            'gross_amount' =>
                $payoutResult->grossAmount,

            'commission_amount' =>
                $payoutResult->commissionAmount,

            'net_amount' =>
                $payoutResult->netAmount
        ];
    })

    // chỉ lấy owner có booking chờ payout
    ->filter(function ($owner) {

        return $owner['pending_bookings'] > 0;
    })

    ->values();
}

    /**
 * Pending payout theo owner
 */
public function getPendingPayoutByOwner(
    int $ownerId
)
{
    /*
    |--------------------------------------------------------------------------
    | Booking eligible
    |--------------------------------------------------------------------------
    */

    $bookings = $this
        ->getEligibleBookingsByOwner(
            $ownerId
        );

    /*
    |--------------------------------------------------------------------------
    | Không có booking
    |--------------------------------------------------------------------------
    */

    if ($bookings->isEmpty()) {

        throw new HttpException(
            404,
            'Không có booking cần payout'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Owner info
    |--------------------------------------------------------------------------
    */

    $ownerName = optional(
        $bookings->first()
            ->details
            ->first()
            ?->field
            ?->court
    )->owner?->name;

    /*
    |--------------------------------------------------------------------------
    | Strategy calculate
    |--------------------------------------------------------------------------
    */

    $payoutResult = $this
        ->payoutStrategy
        ->calculate($bookings);

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return [

        'owner_id' => $ownerId,

        'owner_name' => $ownerName,

        /*
        |--------------------------------------------------------------------------
        | Amounts
        |--------------------------------------------------------------------------
        */

        'gross_amount' =>
            $payoutResult->grossAmount,

        'commission_percent' =>
            $payoutResult->commissionPercent,

        'commission_amount' =>
            $payoutResult->commissionAmount,

        'net_amount' =>
            $payoutResult->netAmount,

        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */

        'total_bookings' =>
            $bookings->count(),

        /*
        |--------------------------------------------------------------------------
        | Booking list
        |--------------------------------------------------------------------------
        */

        'bookings' => $bookings

            ->map(function ($booking) {

                return [

                    'booking_id' => $booking->id,

                    'user' =>
                        $booking->user?->name,

                    'amount' =>
                        $booking->total_price,

                    'booking_date' => optional(
                        $booking
                            ->details
                            ->first()
                    )->booking_date
                ];
            })

            ->values()
    ];
}

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYOUT
    |--------------------------------------------------------------------------
    */

    /**
     * Tạo payout cho owner
     */
    public function createPayout(
        int $ownerId,
        ?string $note = null
    )
    {
        return DB::transaction(function () use (
            $ownerId,
            $note
        ) {

            /*
            |--------------------------------------------------------------------------
            | Booking eligible
            |--------------------------------------------------------------------------
            */

            $bookings = $this
                ->getEligibleBookingsByOwner(
                    $ownerId
                );

            /*
            |--------------------------------------------------------------------------
            | Không có booking
            |--------------------------------------------------------------------------
            */

            if ($bookings->isEmpty()) {

                throw new HttpException(
                    422,
                    'Không có booking cần payout'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Strategy calculate payout
            |--------------------------------------------------------------------------
            */

            $payoutResult = $this
                ->payoutStrategy
                ->calculate($bookings);

            /*
            |--------------------------------------------------------------------------
            | Tạo payout
            |--------------------------------------------------------------------------
            */

            $payout = OwnerPayout::create([

                'owner_id' => $ownerId,

                'gross_amount' =>
                    $payoutResult->grossAmount,

                'commission_percent' =>
                    $payoutResult->commissionPercent,

                'commission_amount' =>
                    $payoutResult->commissionAmount,

                'net_amount' =>
                    $payoutResult->netAmount,

                'note' => $note,

                'status' => 'pending'
            ]);

            /*
            |--------------------------------------------------------------------------
            | Link bookings
            |--------------------------------------------------------------------------
            */

            Booking::query()

                ->whereIn(
                    'id',
                    $bookings->pluck('id')
                )

                ->update([

                    'payout_id' => $payout->id
                ]);

            /*
            |--------------------------------------------------------------------------
            | Return
            |--------------------------------------------------------------------------
            */

            return $payout->load([

                'owner:id,name,email',

                'bookings'
            ]);
        });
    }

    /**
 * Danh sách payout
 */
public function getPayouts(array $filters)
{
    $query = OwnerPayout::query()

        ->with([

            'owner:id,name,email',

            'bookings'
        ]);

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
    | Filter owner
    |--------------------------------------------------------------------------
    */

    if (!empty($filters['owner_id'])) {

        $query->where(
            'owner_id',
            $filters['owner_id']
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
    |
    | pending lên đầu
    | paid xuống dưới
    |
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

/**
 * Admin xác nhận đã thanh toán payout
 */
public function payPayout(
    int $payoutId,
    ?string $note = null
)
{
    return DB::transaction(function () use (
        $payoutId,
        $note
    ) {

        /*
        |--------------------------------------------------------------------------
        | Find payout
        |--------------------------------------------------------------------------
        */

        $payout = OwnerPayout::query()

            ->with([
                'owner:id,name,email'
            ])

            ->findOrFail($payoutId);

        /*
        |--------------------------------------------------------------------------
        | Already paid
        |--------------------------------------------------------------------------
        */

        if ($payout->status === 'paid') {

            throw new HttpException(
                422,
                'Payout đã được thanh toán'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update payout
        |--------------------------------------------------------------------------
        */

        $payout->update([

            'status' => 'paid',

            'paid_at' => now(),

            'note' => $note
        ]);

        event(new PayoutPaidEvent(
            ownerId: $payout->owner_id,
            payoutId: $payout->id,
            amount: $payout->net_amount
        ));

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return $payout->fresh([
            'owner:id,name,email'
        ]);
    });
}
public function getPayoutDetail(int $id)
{
    $payout = OwnerPayout::with([
        'owner',
        'bookings.user'
    ])->findOrFail($id);

    return [
        'id' => $payout->id,

        'owner_id' => $payout->owner_id,

        'owner_name' => $payout->owner?->name,

        'owner_email' => $payout->owner?->email,

        'gross_amount' => $payout->gross_amount,

        'commission_percent' => $payout->commission_percent,

        'commission_amount' => $payout->commission_amount,

        'net_amount' => $payout->net_amount,

        'status' => $payout->status,

        'note' => $payout->note,

        'paid_at' => $payout->paid_at,

        'created_at' => $payout->created_at,

        'total_bookings' => $payout->bookings->count(),

        'bookings' => $payout->bookings->map(function ($booking) {

            return [

                'booking_id' => $booking->id,

                'user' => $booking->user?->name,

                'booking_date' => $booking->booking_date,

                'amount' => $booking->total_price,

                'payment_method' => $booking->payment_method,

                'status' => $booking->status,
            ];
        })
    ];
}
}