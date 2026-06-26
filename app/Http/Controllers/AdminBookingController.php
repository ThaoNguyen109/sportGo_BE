<?php

namespace App\Http\Controllers;

use App\Services\AdminBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class AdminBookingController extends Controller
{
    public function __construct(
        private AdminBookingService $adminBookingService
    ) {}

    /**
     * GET /admin/bookings
     */
    public function getAllBookings(
        Request $request
    ): JsonResponse {

        try {

            $bookings = $this->adminBookingService
                ->getAllBookings($request);

            return response()->json([
                'message' => $bookings->total() > 0
                    ? 'Lấy danh sách booking thành công'
                    : 'Không có booking nào',

                'data' => $bookings
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

     public function getBookingDetail(
        $bookingId
    ): JsonResponse {

        try {

            $booking = $this->adminBookingService
                ->getBookingDetail($bookingId);

            return response()->json([

                'message' => 'Lấy chi tiết booking thành công',

                'data' => $booking
            ]);

        } catch (Exception $e) {

            return response()->json([

                'message' => 'Lỗi server',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    public function getBookingStats(): JsonResponse
    {
        try {
            $stats = $this->adminBookingService
                ->getBookingStats();

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

    /**
 * Hoàn tiền booking
 */
public function refundBooking(
    Request $request,
    $bookingId
): JsonResponse {

    try {
        $booking = $this->adminBookingService
            ->refundBooking($bookingId);

        return response()->json([

            'message' => 'Hoàn tiền thành công',

            'data' => $booking

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

/**
 * Thống kê doanh thu hệ thống
 */
public function getRevenue(
    Request $request
): JsonResponse {

    try {

        $validated = $request->validate([

            'type' => 'required|in:day,week,month,year'
        ]);

        $data = $this->adminBookingService
            ->getRevenue(
                $validated['type']
            );

        return response()->json([

            'message' => 'Lấy thống kê doanh thu thành công',

            'data' => $data
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