<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use Illuminate\Support\Str;

/**
 * ZaloPayGateway
 *
 * Pattern: Adapter (Concrete Adapter) + Factory Method (Concrete Product)
 * Reason: Chuyển đổi (Adapt) API của ZaloPay thành interface PaymentGatewayInterface.
 *         Đây là stub sẵn sàng để tích hợp ZaloPay trong tương lai.
 * Uses:   HttpFacade (Facade Pattern) để gửi request HTTP.
 */
class ZaloPayGateway implements PaymentGatewayInterface
{
    private string $appId;
    private string $key1;
    private string $endpoint;

    public function __construct(private HttpFacade $http)
    {
        $this->appId    = config('zalopay.app_id', '');
        $this->key1     = config('zalopay.key1', '');
        $this->endpoint = config('zalopay.endpoint', 'https://sb-openapi.zalopay.vn/v2/create');
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Implement ZaloPay payment creation logic.
     * @throws \Exception
     */
    public function createPayment(string $orderId, string $requestId, int $amount, string $info): array
    {
        // TODO: Tích hợp ZaloPay API
        // Tham khảo: https://docs.zalopay.vn/v2/
        throw new \Exception('ZaloPay gateway chưa được tích hợp.', 501);
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Implement ZaloPay HMAC-SHA256 signature verification.
     */
    public function verifySignature(array $data): bool
    {
        // TODO: Xác thực chữ ký ZaloPay (mac field)
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function generateOrderId(int $bookingId): string
    {
        return 'ZALOPAY_' . $bookingId . '_' . time();
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
        return 'zalopay';
    }
}
