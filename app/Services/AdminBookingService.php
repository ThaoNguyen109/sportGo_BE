<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Carbon\Carbon;
use App\Models\BookingDetail;
use App\Events\RefundCompletedEvent;

class AdminBookingService
{
    /**
     * Lấy tất cả booking + filter
     */
    public function getAllBookings(
        Request $request
    ) {

        $query = Booking::query()

            ->with([
                'user',

                'details.field.court'
            ]);

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER PAYMENT METHOD
        |--------------------------------------------------------------------------
        */

        if ($request->filled('payment_method')) {

            $query->where(
                'payment_method',
                $request->payment_method
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER USER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('user_id')) {

            $query->where(
                'user_id',
                $request->user_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SEARCH USER NAME
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $query->whereHas('user', function ($q) use ($request) {

                $q->where(
                    'name',
                    'like',
                    '%' . $request->search . '%'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER OWNER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('owner_id')) {

            $query->whereHas(
                'details.field.court',
                function ($q) use ($request) {

                    $q->where(
                        'owner_id',
                        $request->owner_id
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER COURT
        |--------------------------------------------------------------------------
        */

        if ($request->filled('court_id')) {

            $query->whereHas(
                'details.field',
                function ($q) use ($request) {

                    $q->where(
                        'court_id',
                        $request->court_id
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER DATE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('date')) {

            $query->whereHas(
                'details',
                function ($q) use ($request) {

                    $q->whereDate(
                        'booking_date',
                        $request->date
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ORDER
        |--------------------------------------------------------------------------
        */

        return $query

            ->latest()

            ->paginate(10);
    }


    /**
     * Chi tiết booking
     */
    public function getBookingDetail($bookingId)
    {
        return Booking::with([

            'user:id,name,email,phone',

            'details.field.court'

        ])->findOrFail($bookingId);
    }

    /**
     * Hoàn tiền booking
     */

public function refundBooking(
    $bookingId
) {

    return DB::transaction(function () use (
        $bookingId
    ) {

        $booking = Booking::findOrFail($bookingId);

        /*
        |--------------------------------------------------------------------------
        | Chỉ refund booking đã cancel
        |--------------------------------------------------------------------------
        */

        if ($booking->status !== 'cancelled') {

            throw new HttpException(
                422,
                'Chỉ booking ở trạng thái "cancelled" (đã yêu cầu hoàn tiền) mới được xử lý hoàn tiền.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $booking->update([

            'status' => 'refunded'
        ]);
        event(new RefundCompletedEvent(
            $booking->id,
            $booking->user_id,
            $booking->total_price
        ));

        return $booking;
    });
}



public function getBookingStats(): array
{
    $query = Booking::query();

    $total = $query->count();

    $pending = (clone $query)->where('status', 'pending')->count();
    $paid = (clone $query)->where('status', 'paid')->count();
    $completed = (clone $query)->where('status', 'completed')->count();
    $cancelled = (clone $query)->where('status', 'cancelled')->count();
    $refunded = (clone $query)->where('status', 'refunded')->count();

    $totalRevenue = Booking::query()
        ->whereIn('status', ['paid', 'completed'])
        ->sum('total_price');

    $topCourts = BookingDetail::query()
        ->select(
            'fields.court_id',
            'courts.name as court_name',
            DB::raw('COUNT(DISTINCT booking_details.booking_id) as booking_count')
        )
        ->join('fields', 'booking_details.field_id', '=', 'fields.id')
        ->join('courts', 'fields.court_id', '=', 'courts.id')
        ->groupBy('fields.court_id', 'courts.name')
        ->orderByDesc('booking_count')
        ->limit(3)
        ->get();

    return [
        'total_bookings' => $total,
        'pending' => $pending,
        'paid' => $paid,
        'completed' => $completed,
        'cancelled' => $cancelled,
        'refunded' => $refunded,
        'total_revenue' => (float) $totalRevenue,
        'top_courts' => $topCourts->map(function ($court) {
            return [
                'court_id' => $court->court_id,
                'court_name' => $court->court_name,
                'booking_count' => (int) $court->booking_count,
            ];
        })->values(),
    ];
}

public function getRevenue($type)
{
    $query = Booking::query()

        ->whereIn('status', [
            'paid',
            'completed'
        ]);

    /*
    |--------------------------------------------------------------------------
    | FILTER TIME
    |--------------------------------------------------------------------------
    */

    switch ($type) {

        case 'day':

            $query->whereDate(
                'created_at',
                Carbon::today()
            );

            break;

        case 'week':

            $query->whereBetween(
                'created_at',
                [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]
            );

            break;

        case 'month':

            $query->whereMonth(
                'created_at',
                Carbon::now()->month
            )
            ->whereYear(
                'created_at',
                Carbon::now()->year
            );

            break;

        case 'year':

            $query->whereYear(
                'created_at',
                Carbon::now()->year
            );

            break;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATE
    |--------------------------------------------------------------------------
    */

    $totalRevenue = $query->sum('total_price');

    $totalBookings = $query->count();

    return [

        'type' => $type,

        'total_revenue' => $totalRevenue,

        'total_bookings' => $totalBookings
    ];
}
}