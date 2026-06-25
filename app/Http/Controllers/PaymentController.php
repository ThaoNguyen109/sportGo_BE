<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/payments/momo/create
     * User confirm booking → tạo link thanh toán MoMo
     */
    public function createMomoPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        try {
            $result = $this->paymentService->initMomoPayment(
                $validated['booking_id'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Tạo link thanh toán thành công.',
                'data'    => [
                    'pay_url'    => $result['payUrl'],   // Frontend redirect user đến đây
                    'order_id'   => $result['orderId'],
                ],
            ]);

        } catch (\Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
            return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * POST /api/payments/momo/ipn
     * MoMo gọi về đây sau khi user thanh toán (server-to-server)
     * KHÔNG cần auth — MoMo gọi trực tiếp
     */
    public function momoIpn(Request $request): JsonResponse
    {
        $data = $request->all();

        $success = $this->paymentService->handleIpn($data);

        // MoMo yêu cầu response 200 để biết đã nhận được IPN
        return response()->json([
            'partnerCode' => $data['partnerCode'] ?? '',
            'orderId'     => $data['orderId'] ?? '',
            'requestId'   => $data['requestId'] ?? '',
            'resultCode'  => $success ? 0 : 1,
            'message'     => $success ? 'Đã nhận IPN' : 'Lỗi xử lý IPN',
        ]);
    }

    /**
     * GET /api/payments/momo/return
     * MoMo redirect user về đây sau thanh toán (client-side)
     */
    public function momoReturn(Request $request): JsonResponse
    {
        $data = $request->all();

        $result = $this->paymentService->handleReturn($data);

        $httpCode = $result['status'] === 'success' ? 200 : 400;

        return response()->json([
            'success' => $result['status'] === 'success',
            'data'    => $result,
        ], $httpCode);
    }
}