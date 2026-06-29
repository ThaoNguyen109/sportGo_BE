<?php

namespace App\Factories;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use App\Services\VnpayGateway;

/**
 * VnpayGatewayFactory
 *
 * Pattern: Factory Method (Concrete Creator)
 * Reason: Đảm nhận việc khởi tạo VnpayGateway.
 *         Để kích hoạt VNPay, chỉ cần đổi binding trong AppServiceProvider
 *         từ MomoGatewayFactory sang VnpayGatewayFactory — không cần chỉnh
 *         bất kỳ dòng code nào trong PaymentService.
 */
class VnpayGatewayFactory extends PaymentGatewayFactory
{
    /**
     * Tạo và trả về một instance VnpayGateway.
     *
     * @return PaymentGatewayInterface
     */
    public function createGateway(): PaymentGatewayInterface
    {
        return new VnpayGateway(new HttpFacade());
    }
}
