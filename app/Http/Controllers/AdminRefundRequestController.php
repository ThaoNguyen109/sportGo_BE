<?php

namespace App\Http\Controllers;

use App\Services\AdminRefundRequestService;
use Illuminate\Http\JsonResponse;
use Exception;

class AdminRefundRequestController extends Controller
{
    public function __construct(
        private AdminRefundRequestService $refundRequestService
    ) {}

    /**
 * Chi tiết refund theo booking
 */
public function getRefundByBooking(
    $bookingId
): JsonResponse
{
    try {

        $data = $this
            ->refundRequestService
            ->getRefundByBooking(
                $bookingId
            );

        return response()->json([

            'message' => 'Lấy thông tin hoàn tiền thành công',

            'data' => $data

        ]);

    } catch (Exception $e) {

        return response()->json([

            'message' => 'Lỗi server',

            'error' => $e->getMessage()

        ], 500);
    }
}
}