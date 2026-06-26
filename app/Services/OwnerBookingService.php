<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Support\Carbon;
use App\Models\Court;
use App\Models\Field;
use App\Models\BookingDetail;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Events\BookingCancelledByOwnerEvent;
use App\Events\NewNotificationEvent;
use App\Events\SendAdminBookingCancelledListener;

class OwnerBookingService
{
    /**
     * Danh sách booking của owner
     */
    public function getCourtBookings($ownerId, $request)
    {
        $query = Booking::query()

            ->with([
                'user:id,name,email',

                'details' => function ($q) {
                    $q->select([
                        'id',
                        'booking_id',
                        'field_id',
                        'booking_date',
                        'start_time',
                        'end_time',
                        'price'
                    ])
                    ->with([
                        'field:id,court_id,name'
                    ]);
                }
            ])

            // 🔐 chỉ booking thuộc sân owner
            ->whereHas(
                'details.field.court',
                function ($q) use ($ownerId) {
                    $q->where('owner_id', $ownerId);
                }
            );

        // 🔍 filter status
        if ($request->has('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        // 🔍 filter date
        if ($request->has('date')) {
            $query->whereHas(
                'details',
                function ($q) use ($request) {
                    $q->where(
                        'booking_date',
                        $request->date
                    );
                }
            );
        }

        return $query
            ->latest()
            ->paginate(10);
    }

    /**
     * Chi tiết booking
     */
    public function getBookingDetail($ownerId, $bookingId)
    {
        $booking = Booking::with([

            'user:id,name,email,phone',

            'details.field.court'

        ])->findOrFail($bookingId);

        // 🔐 check quyền owner
        $hasPermission = $booking->details
            ->contains(function ($detail) use ($ownerId) {

                return $detail->field
                    ->court
                    ->owner_id == $ownerId;
            });

        if (!$hasPermission) {
            throw new AuthorizationException(
                'Không có quyền xem booking này'
            );
        }

        return $booking;
    }

    public function cancelBooking(
    $ownerId,
    $bookingId,
    $reason
)
{
    $booking = Booking::with([
        'details.field.court'
    ])->findOrFail($bookingId);

    // 🔐 check owner
    $hasPermission = $booking->details
        ->contains(function ($detail) use ($ownerId) {

            return $detail->field
                ->court
                ->owner_id == $ownerId;
        });

    if (!$hasPermission) {
        throw new AuthorizationException(
            'Không có quyền hủy booking này'
        );
    }

    // ❌ booking đã hủy
    if ($booking->status === 'cancelled') {
        throw new HttpException(
            422,
            'Booking đã bị hủy'
        );
    }

    // ❌ booking hoàn thành
    if ($booking->status === 'completed') {
        throw new HttpException(
            422,
            'Booking đã hoàn thành'
        );
    }

    $booking->update([
        'status' => 'cancelled',
        'cancel_reason' => $reason,
        'cancelled_by' => 'owner'
    ]);
    event(new BookingCancelledByOwnerEvent(

    bookingId: $booking->id,

    userId: $booking->user_id,

    ownerId: $ownerId,

    reason: $reason
));

    return $booking->fresh([
        'details.field'
    ]);
}

public function getDashboard($ownerId): array
    {
        /*
        |--------------------------------------------------------------------------
        | Lấy tất cả booking thuộc owner
        |--------------------------------------------------------------------------
        */

        $bookingIds = BookingDetail::query()

            ->join('fields', 'booking_details.field_id', '=', 'fields.id')

            ->join('courts', 'fields.court_id', '=', 'courts.id')

            ->where('courts.owner_id', $ownerId)

            ->distinct()

            ->pluck('booking_details.booking_id');

        /*
        |--------------------------------------------------------------------------
        | BOOKING TOTAL
        |--------------------------------------------------------------------------
        */

        $totalBookings = Booking::whereIn(
            'id',
            $bookingIds
        )->count();

        $todayBookings = Booking::whereIn(
            'id',
            $bookingIds
        )
        ->whereDate(
            'created_at',
            Carbon::today()
        )
        ->count();

        /*
        |--------------------------------------------------------------------------
        | REVENUE
        |--------------------------------------------------------------------------
        */

        $totalRevenue = Booking::whereIn(
            'id',
            $bookingIds
        )
        ->where('status', 'paid')
        ->sum('total_price');

        $todayRevenue = Booking::whereIn(
            'id',
            $bookingIds
        )
        ->where('status', 'paid')
        ->whereDate(
            'created_at',
            Carbon::today()
        )
        ->sum('total_price');


        return [
            'total_bookings' => $totalBookings,

            'today_bookings' => $todayBookings,

            'total_revenue' => $totalRevenue,

            'today_revenue' => $todayRevenue,

        ];
    }

    public function getBookingStats($ownerId): array
    {
        /*
        |--------------------------------------------------------------------------
        | Lấy booking thuộc owner
        |--------------------------------------------------------------------------
        */

        $bookingIds = BookingDetail::query()

            ->join('fields', 'booking_details.field_id', '=', 'fields.id')

            ->join('courts', 'fields.court_id', '=', 'courts.id')

            ->where('courts.owner_id', $ownerId)

            ->distinct()

            ->pluck('booking_details.booking_id');

        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */

        $total = Booking::whereIn('id', $bookingIds)->count();

        $pending = Booking::whereIn('id', $bookingIds)
            ->where('status', 'pending')
            ->count();

        $paid = Booking::whereIn('id', $bookingIds)
            ->where('status', 'paid')
            ->count();

        $cancelled = Booking::whereIn('id', $bookingIds)
            ->where('status', 'cancelled')
            ->count();

        $refunded = Booking::whereIn('id', $bookingIds)
            ->where('status', 'refunded')
            ->count();

        return [
            'total' => $total,

            'pending' => $pending,

            'paid' => $paid,

            'cancelled' => $cancelled,

            'refunded' => $refunded,
        ];
    }


public function getRevenue($ownerId, $type): array
{
    /*
    |--------------------------------------------------------------------------
    | Query base
    |--------------------------------------------------------------------------
    */

    $query = BookingDetail::query()

        ->join('bookings', 'booking_details.booking_id', '=', 'bookings.id')

        ->join('fields', 'booking_details.field_id', '=', 'fields.id')

        ->join('courts', 'fields.court_id', '=', 'courts.id')

        ->where('courts.owner_id', $ownerId)

        ->where('bookings.status', 'paid');

    /*
    |--------------------------------------------------------------------------
    | Filter theo thời gian
    |--------------------------------------------------------------------------
    */

    switch ($type) {

        case 'day':

            $query->whereDate(
                'booking_details.booking_date',
                Carbon::today()
            );

            break;

        case 'week':

            $query->whereBetween(
                'booking_details.booking_date',
                [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]
            );

            break;

        case 'month':

            $query->whereMonth(
                'booking_details.booking_date',
                Carbon::now()->month
            )
            ->whereYear(
                'booking_details.booking_date',
                Carbon::now()->year
            );

            break;
    }

    /*
    |--------------------------------------------------------------------------
    | Revenue theo từng sân
    |--------------------------------------------------------------------------
    */

    $revenues = $query

        ->select(
            'courts.id',
            'courts.name',

            DB::raw('SUM(booking_details.price) as revenue')
        )

        ->groupBy(
            'courts.id',
            'courts.name'
        )

        ->orderByDesc('revenue')

        ->get();

    /*
    |--------------------------------------------------------------------------
    | Total revenue
    |--------------------------------------------------------------------------
    */

    $totalRevenue = $revenues->sum('revenue');

    return [
        'type' => $type,

        'total_revenue' => $totalRevenue,

        'courts' => $revenues
    ];
}



public function getCourtCalendar(
    $ownerId,
    $courtId,
    $date
): array {

    /*
    |--------------------------------------------------------------------------
    | COURT
    |--------------------------------------------------------------------------
    */

    $court = Court::with('fields')

        ->where('owner_id', $ownerId)

        ->findOrFail($courtId);

    /*
    |--------------------------------------------------------------------------
    | ALL BOOKINGS
    |--------------------------------------------------------------------------
    */

    $bookings = BookingDetail::query()

        ->join(
            'bookings',
            'booking_details.booking_id',
            '=',
            'bookings.id'
        )

        ->join(
            'users',
            'bookings.user_id',
            '=',
            'users.id'
        )

        ->whereIn(
            'booking_details.field_id',
            $court->fields->pluck('id')
        )

        ->whereDate(
            'booking_details.booking_date',
            $date
        )

        ->orderBy('booking_details.start_time')

        ->get([
            'booking_details.field_id',

            'booking_details.start_time',

            'booking_details.end_time',

            'bookings.status',

            'users.name as user_name'
        ]);

    /*
    |--------------------------------------------------------------------------
    | GROUP BY FIELD
    |--------------------------------------------------------------------------
    */

    $fields = [];

    foreach ($court->fields as $field) {

        $fieldBookings = $bookings
            ->where('field_id', $field->id);

        $fields[] = [

            'field_id' => $field->id,

            'field_name' => $field->name,

            'booked_slots' => $fieldBookings
                ->map(function ($slot) {

                    return [

                        'start' => substr(
                            $slot->start_time,
                            0,
                            5
                        ),

                        'end' => substr(
                            $slot->end_time,
                            0,
                            5
                        ),

                        'status' => $slot->status,

                        'user' => $slot->user_name,
                    ];

                })->values()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSE
    |--------------------------------------------------------------------------
    */

    return [

        'court_id' => $court->id,

        'court_name' => $court->name,

        'date' => $date,

        'fields' => $fields
    ];
}
}