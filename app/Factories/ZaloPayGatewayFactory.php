<?php

namespace App\Factories;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use App\Services\ZaloPayGateway;

/**
 * ZaloPayGatewayFactory
 *
 * Pattern: Factory Method (Concrete Creator)
 * Reason: Đảm nhận việc khởi tạo ZaloPayGateway.
 *         Để kích hoạt ZaloPay, chỉ cần đổi binding trong AppServiceProvider
 *         từ MomoGatewayFactory sang ZaloPayGatewayFactory.
 */
class ZaloPayGatewayFactory extends PaymentGatewayFactory
{
    /**
     * Tạo và trả về một instance ZaloPayGateway.
     *
     * @return PaymentGatewayInterface
     */
    public function createGateway(): PaymentGatewayInterface
    {
        return new ZaloPayGateway(new HttpFacade());
    }
}
