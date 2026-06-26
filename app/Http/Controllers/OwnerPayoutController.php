<?php

namespace App\Http\Controllers;

use App\Services\OwnerPayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Exception;

class OwnerPayoutController extends Controller
{
    public function __construct(
        private OwnerPayoutService $ownerPayoutService
    ) {}

    /**
     * Danh sách payout của owner
     */
    public function getMyPayouts(
        Request $request
    ): JsonResponse
    {
        try {

            $data = $this
                ->ownerPayoutService
                ->getOwnerPayouts(

                    auth()->id(),

                    $request->all()
                );

            return response()->json([

                'message' =>
                    'Lấy danh sách payout thành công',

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