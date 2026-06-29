<?php

namespace App\Contracts;

/**
 * PaymentGatewayInterface
 *
 * Pattern: Adapter (Target Interface) + Factory Method (Product Interface)
 * Reason: Định nghĩa hợp đồng chung cho tất cả các cổng thanh toán
 *         (MoMo, VNPay, ZaloPay,...). PaymentService chỉ phụ thuộc vào
 *         interface này, không phụ thuộc vào bất kỳ cài đặt cụ thể nào.
 *         Tuân thủ Dependency Inversion Principle (SOLID-D).
 */
interface PaymentGatewayInterface
{
    /**
     * Tạo giao dịch thanh toán và trả về URL thanh toán cùng metadata.
     *
     * @param string $orderId    Mã đơn hàng duy nhất
     * @param string $requestId  Mã request duy nhất
     * @param int    $amount     Số tiền (VNĐ)
     * @param string $info       Mô tả đơn hàng hiển thị trên cổng thanh toán
     * @return array             Trả về ['payUrl', 'orderId', 'requestId', ...]
     */
    public function createPayment(string $orderId, string $requestId, int $amount, string $info): array;

    /**
     * Xác thực chữ ký chống giả mạo từ callback/IPN của cổng thanh toán.
     *
     * @param array $data Dữ liệu raw nhận được từ callback
     * @return bool
     */
    public function verifySignature(array $data): bool;

    /**
     * Sinh mã đơn hàng (orderId) duy nhất cho mỗi giao dịch.
     *
     * @param int $bookingId
     * @return string
     */
    public function generateOrderId(int $bookingId): string;

    /**
     * Sinh mã request (requestId) duy nhất.
     *
     * @return string
     */
    public function generateRequestId(): string;

    /**
     * Trả về định danh chuỗi của cổng thanh toán (ví dụ: 'momo', 'vnpay', 'zalopay').
     * Dùng để lưu trường payment_method vào database.
     *
     * @return string
     */
    public function process(): string;
}
