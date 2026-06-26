<?php

namespace App\Http\Controllers;

use App\Services\OwnerBankAccountService;
use Illuminate\Http\JsonResponse;

use Exception;

class AdminOwnerBankAccountController extends Controller
{
    public function __construct(
        private OwnerBankAccountService $bankAccountService
    ) {}

    /**
     * Admin xem bank account owner
     */
    public function getOwnerBankAccount(
        $ownerId
    ): JsonResponse
    {
        try {

            $data = $this
                ->bankAccountService
                ->getOwnerBankAccount(
                    $ownerId
                );

            return response()->json([

                'message' =>
                    'Lấy tài khoản ngân hàng thành công',

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