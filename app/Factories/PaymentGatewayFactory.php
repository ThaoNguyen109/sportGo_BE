<?php

namespace App\Factories;

use App\Contracts\PaymentGatewayInterface;

/**
 * PaymentGatewayFactory
 *
 * Pattern: Factory Method (Abstract Creator)
 * Reason: Định nghĩa hợp đồng khởi tạo gateway thanh toán.
 *         Các concrete factory con sẽ quyết định class nào được tạo ra,
 *         tuân thủ Open/Closed Principle (SOLID-O).
 */
abstract class PaymentGatewayFactory
{
    /**
     * Factory Method — subclass override để tạo product cụ thể.
     *
     * @return PaymentGatewayInterface
     */
    abstract public function createGateway(): PaymentGatewayInterface;

    /**
     * Template method: trả về gateway đã khởi tạo sẵn sàng sử dụng.
     * Client code gọi getGateway() thay vì createGateway() trực tiếp.
     *
     * @return PaymentGatewayInterface
     */
    public function getGateway(): PaymentGatewayInterface
    {
        return $this->createGateway();
    }
}
