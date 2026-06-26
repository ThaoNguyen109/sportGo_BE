<?php

namespace App\Http\Controllers;

use App\Services\AdminPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class AdminPayoutController extends Controller
{
    public function __construct(
        private AdminPayoutService $adminPayoutService
    ) {}

    /**
     * Danh sách owner có booking chờ payout
     */
    public function getAllOwnersPendingPayout(): JsonResponse
    {
        return response()->json([
            'owners' => $this
                ->adminPayoutService
                ->getAllOwnersPendingPayout()
        ]);
    }

    /**
     * Pending payout theo owner
     */
    public function getPendingPayoutByOwner(
        $ownerId
    ): JsonResponse {
        $ownerId = (int) $ownerId;
        try {

            $data = $this->adminPayoutService
                ->getPendingPayoutByOwner(
                    $ownerId
                );

            return response()->json([
                'message' => 'Lấy payout pending thành công',
                'data' => $data
            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create payout
    |--------------------------------------------------------------------------
    */

    public function createPayout(
        Request $request,
        int $ownerId
    ): JsonResponse {
        try {

            $validated = $request->validate([
                'note' => 'nullable|string'
            ]);

            $payout = $this->adminPayoutService
                ->createPayout(
                    $ownerId,
                    $validated['note'] ?? null
                );

            return response()->json([
                'message' => 'Tạo payout thành công',
                'data' => $payout
            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Danh sách payout
     */
    public function getPayouts(
        Request $request
    ): JsonResponse {
        try {

            $data = $this->adminPayoutService
                ->getPayouts(
                    $request->all()
                );

            return response()->json([
                'message' => 'Lấy danh sách payout thành công',
                'data' => $data
            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin xác nhận đã thanh toán payout
     */
    public function payPayout(
        Request $request,
        int $id
    ): JsonResponse {
        try {

            $validated = $request->validate([
                'note' => 'nullable|string|max:1000'
            ]);

            $payout = $this->adminPayoutService
                ->payPayout(
                    $id,
                    $validated['note'] ?? null
                );

            return response()->json([
                'message' => 'Thanh toán payout thành công',
                'data' => $payout
            ]);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Lỗi server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết payout
     */
    public function getPayoutDetail(int $id): JsonResponse
{
    try {

        return response()->json([

            'message' => 'Lấy chi tiết payout thành công',

            'data' => $this->adminPayoutService
                ->getPayoutDetail($id)

        ]);

    } catch (Exception $e) {

        return response()->json([

            'message' => 'Lỗi server',

            'error' => $e->getMessage()

        ],500);

    }
}
}