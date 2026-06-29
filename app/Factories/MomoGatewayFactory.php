<?php

namespace App\Factories;

use App\Contracts\PaymentGatewayInterface;
use App\Services\HttpFacade;
use App\Services\MomoGateway;

/**
 * MomoGatewayFactory
 *
 * Pattern: Factory Method (Concrete Creator)
 * Reason: Đảm nhận việc khởi tạo MomoGateway với đúng dependencies.
 *         PaymentService chỉ biết đến abstract factory, không biết
 *         cụ thể Gateway nào đang được tạo ra (Loose Coupling).
 */
class MomoGatewayFactory extends PaymentGatewayFactory
{
    /**
     * Tạo và trả về một instance MomoGateway.
     *
     * @return PaymentGatewayInterface
     */
    public function createGateway(): PaymentGatewayInterface
    {
        return new MomoGateway(new HttpFacade());
    }
}
