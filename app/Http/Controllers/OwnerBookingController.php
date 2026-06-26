<?php

namespace App\Http\Controllers;

use App\Services\OwnerBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Exception;

class OwnerBookingController extends Controller
{
    public function __construct(
        private OwnerBookingService $ownerBookingService
    ) {}

    /**
     * Danh sách booking của sân owner
     */
    public function getCourtBookings(Request $request): JsonResponse
    {
        try {

            $bookings = $this->ownerBookingService
                ->getCourtBookings(
                    auth()->id(),
                    $request
                );

            return response()->json([
                'message' => 'Lấy danh sách booking thành công',
                'data' => $bookings
            ]);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Chi tiết booking
     */
    public function getBookingDetail($id): JsonResponse
    {
        try {

            $booking = $this->ownerBookingService
                ->getBookingDetail(
                    auth()->id(),
                    $id
                );

            return response()->json([
                'message' => 'Lấy chi tiết booking thành công',
                'data' => $booking
            ]);

        } catch (AuthorizationException $e) {

            return response()->json([
                'message' => $e->getMessage()
            ], 403);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Không tìm thấy booking'
            ], 404);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking(
    Request $request,
    $id
): JsonResponse
{
    try {

        $validated = $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = $this->ownerBookingService
            ->cancelBooking(
                auth()->id(),
                $id,
                $validated['reason']
            );

        return response()->json([
            'message' => 'Hủy booking thành công',
            'data' => $booking
        ]);

    } catch (AuthorizationException $e) {

        return response()->json([
            'message' => $e->getMessage()
        ], 403);

    } catch (ModelNotFoundException $e) {

        return response()->json([
            'message' => 'Không tìm thấy booking'
        ], 404);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function dashboard(): JsonResponse
{
    try {

        $dashboard = $this->ownerBookingService
            ->getDashboard(
                auth()->id()
            );

        return response()->json([
            'message' => 'Lấy dashboard thành công',
            'data' => $dashboard
        ]);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function bookingStats(): JsonResponse
{
    try {

        $stats = $this->ownerBookingService
            ->getBookingStats(auth()->id());

        return response()->json([
            'message' => 'Lấy thống kê booking thành công',
            'data' => $stats
        ]);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function getRevenue(Request $request): JsonResponse
{
    try {

        $validated = $request->validate([
            'type' => 'required|in:day,week,month'
        ]);

        $revenue = $this->ownerBookingService
            ->getRevenue(
                auth()->id(),
                $validated['type']
            );

        return response()->json([
            'message' => 'Lấy doanh thu thành công',
            'data' => $revenue
        ]);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}

  public function courtCalendar(
    Request $request,
    $courtId
): JsonResponse
{
    try {

        $validated = $request->validate([
            'date' => 'nullable|date'
        ]);

        $date = $validated['date']
            ?? now()->toDateString();

        $calendar = $this->ownerBookingService
            ->getCourtCalendar(
                auth()->id(),
                $courtId,
                $date
            );

        return response()->json([
            'message' => 'Lấy lịch sân thành công',
            'date' => $date,
            'data' => $calendar
        ]);

    } catch (ValidationException $e) {

        return response()->json([
            'message' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {

        return response()->json([
            'message' => 'Lỗi server',
            'error' => $e->getMessage()
        ], 500);
    }
}  

}