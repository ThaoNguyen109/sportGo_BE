<?php

namespace App\Http\Controllers;

use App\Services\OwnerBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Exception;

class OwnerBankAccountController extends Controller
{
    public function __construct(
        private OwnerBankAccountService $bankAccountService
    ) {}

    /**
     * Tạo/cập nhật bank account
     */
    public function saveBankAccount(
        Request $request
    ): JsonResponse
    {
        try {

            $validated = $request->validate([

                'bank_name' =>
                    'required|string|max:255',

                'bank_code' =>
                    'nullable|string|max:50',

                'account_number' =>
                    'required|string|max:255',

                'account_name' =>
                    'required|string|max:255',

                'qr_image' =>
                    'nullable|image|max:2048'
            ]);

            $bankAccount = $this
                ->bankAccountService
                ->saveBankAccount(

                    auth()->id(),

                    $validated,

                    $request->file('qr_image')
                );

            return response()->json([

                'message' =>
                    'Lưu tài khoản ngân hàng thành công',

                'data' => $bankAccount
            ]);

        } catch (Exception $e) {

            return response()->json([

                'message' => 'Lỗi server',

                'error' => $e->getMessage()

            ], 500);
        }
    }

    /**
 * Owner xem bank account
 */
public function getMyBankAccount(): JsonResponse
{
    try {

        $data = $this
            ->bankAccountService
            ->getMyBankAccount(
                auth()->id()
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