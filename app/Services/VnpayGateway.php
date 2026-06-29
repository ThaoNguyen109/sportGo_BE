<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use Illuminate\Support\Str;

/**
 * VnpayGateway
 *
 * Pattern: Adapter (Concrete Adapter) + Factory Method (Concrete Product)
 * Reason: Chuyển đổi (Adapt) API của VNPay thành interface PaymentGatewayInterface.
 *         Đây là stub sẵn sàng để tích hợp VNPay trong tương lai.
 * Uses:   HttpFacade (Facade Pattern) để gửi request HTTP.
 */
class VnpayGateway implements PaymentGatewayInterface
{
    private string $tmnCode;
    private string $hashSecret;
    private string $endpoint;

    public function __construct(private HttpFacade $http)
    {
        $this->tmnCode    = config('vnpay.tmn_code', '');
        $this->hashSecret = config('vnpay.hash_secret', '');
        $this->endpoint   = config('vnpay.endpoint', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Implement VNPay payment creation logic.
     * @throws \Exception
     */
    public function createPayment(string $orderId, string $requestId, int $amount, string $info): array
    {
        // TODO: Tích hợp VNPay API
        // Tham khảo: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
        throw new \Exception('VNPay gateway chưa được tích hợp.', 501);
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Implement VNPay signature verification logic.
     */
    public function verifySignature(array $data): bool
    {
        // TODO: Xác thực chữ ký VNPay (vnp_SecureHash)
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function generateOrderId(int $bookingId): string
    {
        return 'VNPAY_' . $bookingId . '_' . time();
    }

    /**
     * {@inheritDoc}
     */
    public function generateRequestId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * {@inheritDoc}
     */
    public function process(): string
    {
        return 'vnpay';
    }
}
